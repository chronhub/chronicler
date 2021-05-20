<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Factory;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Connection;
use Chronhub\Chronicler\PgsqlChronicler;
use Illuminate\Contracts\Config\Repository;
use Chronhub\Chronicler\GenericEventChronicler;
use Illuminate\Contracts\Foundation\Application;
use Chronhub\Chronicler\Exception\RuntimeException;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Driver\InMemory\InMemoryChronicler;
use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\GenericTransactionalEventChronicler;
use Chronhub\Chronicler\Support\Contracts\StreamPersistence;
use Chronhub\Chronicler\Support\Contracts\WriteLockStrategy;
use Chronhub\Chronicler\Support\Contracts\EventableChronicler;
use Chronhub\Chronicler\Driver\Connection\WriteLock\NoWriteLock;
use Chronhub\Chronicler\Driver\Connection\Loader\LazyQueryLoader;
use Chronhub\Chronicler\Support\Contracts\Tracking\StreamTracker;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;
use Chronhub\Chronicler\Driver\Connection\Loader\StreamEventLoader;
use Chronhub\Chronicler\Driver\Connection\WriteLock\PgsqlWriteLock;
use Chronhub\Chronicler\Support\Contracts\Factory\ChroniclerManager;
use Chronhub\Chronicler\Support\Contracts\Model\EventStreamProvider;
use Chronhub\Chronicler\Driver\InMemory\InMemoryTransactionalChronicler;
use Chronhub\Chronicler\Support\Contracts\Tracking\TransactionalStreamTracker;
use function is_array;
use function is_string;

final class DefaultChroniclerManager implements ChroniclerManager
{
    private array $customChroniclers = [];
    private array $chroniclers = [];
    private array $config;

    public function __construct(private Application $app)
    {
        $this->config = $app->get(Repository::class)->get('chronicler');
    }

    public function create(string $name = 'default'): Chronicler
    {
        if ('default' === $name) {
            $name = $this->fromChronicler('connections.default');
        }

        if ( ! is_string($name) || '' === $name) {
            throw new InvalidArgumentException('Invalid chronicler name');
        }

        if ($chronicler = $this->chroniclers[$name] ?? null) {
            return $chronicler;
        }

        return $this->chroniclers[$name] = $this->resolveChronicleDriver($name);
    }

    public function extends(string $name, callable $chronicler): void
    {
        $this->customChroniclers[$name] = $chronicler;
    }

    private function resolveChronicleDriver(string $name): Chronicler
    {
        if ($customChronicler = $this->customChroniclers[$name] ?? null) {
            return $customChronicler($this->app, $this->config);
        }

        $config = $this->fromChronicler("connections.$name");

        if ( ! is_array($config)) {
            throw new RuntimeException("Chronicle store connection $name not found");
        }

        $chronicler = $this->resolveChronicleStore($name, $config);

        if ($chronicler instanceof EventableChronicler) {
            $this->attachStreamSubscribers($chronicler, $config);
        }

        return $chronicler;
    }

    private function resolveChronicleStore(string $name, array $config): Chronicler
    {
        $driver = $config['driver'];

        $method = 'create' . Str::studly($driver . 'Driver');

        /* @covers createInMemoryDriver */
        /* @covers createPgsqlDriver */
        if ( ! method_exists($this, $method)) {
            throw new RuntimeException("Unable to resolve chronicle store with name $name and driver $driver");
        }

        $chronicler = $this->$method($config);

        if ('in_memory' === $driver) {
            return $chronicler;
        }

        return $this->resolveEventChroniclerDecorator($chronicler, $config);
    }

    private function resolveEventChroniclerDecorator(Chronicler $chronicler, array $config): Chronicler
    {
        $options = $config['options'] ?? [];

        if (false === $options || false === ($options['use_event_decorator'] ?? false)) {
            return $chronicler;
        }

        $tracker = $this->determineTracker($config);

        if ( ! $tracker instanceof StreamTracker) {
            throw new RuntimeException('Use of event chronicler decorator require a valid tracker id');
        }

        if ($chronicler instanceof TransactionalChronicler && $tracker instanceof TransactionalStreamTracker) {
            return new GenericTransactionalEventChronicler($chronicler, $tracker);
        }

        if ($chronicler instanceof EventableChronicler) {
            return new GenericEventChronicler($chronicler, $tracker);
        }

        throw new RuntimeException('Unable to configure chronicler event decorator');
    }

    private function createInMemoryDriver(array $config): Chronicler
    {
        $options = $config['options'] ?? false;

        $eventStreamProvider = $this->createEventStreamProvider($config);

        if (false === $options) {
            return new InMemoryChronicler($eventStreamProvider);
        }

        if (true === $options['use_transaction']) {
            return new InMemoryTransactionalChronicler($eventStreamProvider);
        }

        $useEventDecorator = $options['use_event_decorator'] ?? false;
        $tracker = $this->determineTracker($config);

        if (true === $useEventDecorator && $tracker instanceof StreamTracker) {
            if ($tracker instanceof TransactionalStreamTracker) {
                return new GenericTransactionalEventChronicler(
                    new InMemoryTransactionalChronicler($eventStreamProvider),
                    $tracker
                );
            }

            return new GenericEventChronicler(
                new InMemoryChronicler($eventStreamProvider), $tracker
            );
        }

        throw new RuntimeException('Unable to configure chronicler event decorator');
    }

    private function createPgsqlDriver(array $config): Chronicler
    {
        /** @var Connection $connection */
        $connection = $this->app['db']->connection('pgsql');

        return $this->resolveConnection($connection, PgsqlChronicler::class, $config);
    }

    private function resolveConnection(Connection $connection, string $chroniclerClass, array $config): Chronicler
    {
        return new $chroniclerClass(
            $connection,
            $this->createEventStreamProvider($config),
            $this->createStreamPersistence($config),
            $this->createWriteLock($connection, $config),
            $this->createStreamEventLoader($config),
        );
    }

    private function createWriteLock(Connection $connection, array $config): WriteLockStrategy
    {
        $writeLock = $config['options']['write_lock'] ?? false;

        if (false === $writeLock) {
            return new NoWriteLock();
        }

        if (true === $writeLock) {
            $driver = $connection->getDriverName();

            return match ($driver) {
                'pgsql' => new PgsqlWriteLock($connection),
                default => throw new RuntimeException("Unavailable write lock strategy for driver $driver"),
            };
        }

        return $this->app->make($writeLock);
    }

    private function createStreamPersistence(array $config): StreamPersistence
    {
        $strategyKey = $config['strategy'] ?? 'default';

        if ('default' === $strategyKey) {
            $strategyKey = $this->fromChronicler('strategy.default');
        }

        $strategy = $this->fromChronicler("strategy.$strategyKey");

        if (null === $persistence = $strategy['persistence'] ?? null) {
            throw new RuntimeException('Unable to determine persistence strategy');
        }

        if ( ! class_exists($persistence) && ! $this->app->bound($persistence)) {
            throw new RuntimeException('Persistence strategy must be a valid class name or a service registered in ioc');
        }

        return $this->app->make($persistence);
    }

    private function createStreamEventLoader(array $config): StreamEventLoader
    {
        $eventLoader = $config['query_loader'] ?? null;

        if (is_string($eventLoader)) {
            return $this->app->make($eventLoader);
        }

        return $this->app->make(LazyQueryLoader::class);
    }

    private function createEventStreamProvider(array $config): EventStreamProvider
    {
        $eventStreamKey = $config['provider'] ?? null;

        $eventStream = $this->fromChronicler("provider.$eventStreamKey");

        if ( ! is_string($eventStream)) {
            throw new RuntimeException('Unable to determine stream provider');
        }

        return $this->app->make($eventStream);
    }

    private function determineTracker(array $config): ?StreamTracker
    {
        $tracker = $config['tracking']['tracker_id'] ?? null;

        return is_string($tracker) ? $this->app->make($tracker) : null;
    }

    private function attachStreamSubscribers(EventableChronicler $chronicler, array $config): void
    {
        $subscribers = $config['tracking']['subscribers'] ?? [];

        array_walk($subscribers, function (string $subscriber) use ($chronicler): void {
            $this->app->make($subscriber)->attachToChronicler($chronicler);
        });
    }

    private function fromChronicler(string $key): mixed
    {
        return Arr::get($this->config, $key);
    }
}
