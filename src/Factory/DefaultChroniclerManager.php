<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Factory;

use Chronhub\Chronicler\Driver\InMemory\InMemoryChronicler;
use Chronhub\Chronicler\Driver\InMemory\InMemoryTransactionalChronicler;
use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\Exception\RuntimeException;
use Chronhub\Chronicler\GenericEventChronicler;
use Chronhub\Chronicler\GenericTransactionalEventChronicler;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Support\Contracts\EventableChronicler;
use Chronhub\Chronicler\Support\Contracts\Factory\ChroniclerManager;
use Chronhub\Chronicler\Support\Contracts\Model\EventStreamProvider;
use Chronhub\Chronicler\Support\Contracts\StreamPersistence;
use Chronhub\Chronicler\Support\Contracts\Tracking\StreamTracker;
use Chronhub\Chronicler\Support\Contracts\Tracking\TransactionalStreamTracker;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;
use Chronhub\Chronicler\Support\Contracts\WriteLockStrategy;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use function is_string;

final class DefaultChroniclerManager implements ChroniclerManager
{
    protected array $customChroniclers = [];
    protected array $chroniclers = [];
    protected array $config;

    public function __construct(private Application $app)
    {
        $this->config = $app->get(Repository::class)->get('chronicler');
    }

    public function create(string $name = 'default'): Chronicler
    {
        if ($name === 'default') {
            $name = $this->fromChronicler('connections.default');
        }

        if (!is_string($name) || $name === "") {
            throw new InvalidArgumentException("Invalid chronicler name");
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

    private function resolveChronicleDriver($name): Chronicler
    {
        if ($customChronicler = $this->customChroniclers[$name] ?? null) {
            return $customChronicler($this->app, $this->config);
        }

        $config = $this->fromChronicler("connections.$name");

        if (!is_array($config)) {
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

        /** @covers createInMemoryDriver */
        /** @covers createPgsqlDriver */
        if (!method_exists($this, $method)) {
            throw new RuntimeException(
                "Unable to resolve chronicle store with name $name and driver $driver"
            );
        }

        $chronicler = $this->$method($config);

        if ($driver === 'in_memory') {
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

        if (!$tracker instanceof StreamTracker) {
            throw new RuntimeException("Use of event chronicler decorator require a valid tracker id");
        }

        if ($chronicler instanceof TransactionalChronicler && $tracker instanceof TransactionalStreamTracker) {
            return new GenericTransactionalEventChronicler($chronicler, $tracker);
        }

        if ($chronicler instanceof EventableChronicler) {
            return new GenericEventChronicler($chronicler, $tracker);
        }

        throw new RuntimeException("Unable to configure chronicler event decorator");
    }

    private function createPgsqlDriver(array $config): Chronicler
    {
        /** @var Connection $connection */
        $connection = $this->app['db']->connection('pgsql');

        $className = '\Chronhub\Chronicler\Connection\PgsqlChronicler';

        if (!class_exists($className)) {
            throw new RuntimeException("Require chronicler connection to be loaded");
        }

        return $this->resolveConnection($connection, $className, $config);
    }

    private function resolveConnection(Connection $connection, string $chroniclerClassName, array $config): Chronicler
    {
        $streamEventLoader = '\Chronhub\Chronicler\Connection\StreamEventLoader';

        return new $chroniclerClassName(
            $connection,
            $this->determineEventStreamProvider($config),
            $this->determineStreamPersistence($config),
            $this->determineWriteLock($connection, $config),
            $this->app->make($streamEventLoader),
        );
    }

    private function determineWriteLock(Connection $connection, array $config): WriteLockStrategy
    {
        $instance = null;

        $useWriteLock = $config['options']['write_lock'] ?? false;

        if (!$useWriteLock) {
            $nullWriteLock = '\Chronhub\Chronicler\Connection\WriteLock\NullWriteLock';

            $instance = new $nullWriteLock();
        }

        $driver = $connection->getDriverName();

        $pgsqlWriteLock = '\Chronhub\Chronicler\Connection\WriteLock\PgsqlWriteLock';

        if (null === $instance && true === $useWriteLock) {
            $instance = match ($driver) {
                'pgsql' => new $pgsqlWriteLock($connection),
                default => throw new RuntimeException("Unavailable write lock strategy for driver $driver"),
            };
        }

        if ($instance instanceof WriteLockStrategy) {
            return $instance;
        }

        throw new RuntimeException("Unavailable write lock strategy for driver $driver");
    }

    private function determineStreamPersistence(array $config): StreamPersistence
    {
        $strategyKey = $config['strategy'] ?? 'default';

        if ($strategyKey === 'default') {
            $strategyKey = $this->fromChronicler("strategy.default");
        }

        $strategy = $this->fromChronicler("strategy.$strategyKey");

        if (null === $persistence = $strategy['persistence'] ?? null) {
            throw new RuntimeException("Unable to determine persistence strategy");
        }

        if (!class_exists($persistence) && !$this->app->bound($persistence)) {
            throw new RuntimeException("Persistence strategy must be a valid class name or a service registered in ioc");
        }

        return $this->app->make($persistence);
    }

    private function createInMemoryDriver(array $config): Chronicler
    {
        $options = $config['options'] ?? false;

        $eventStreamProvider = $this->determineEventStreamProvider($config);

        if (false === $options) {
            return new InMemoryChronicler($eventStreamProvider);
        }

        if ($options['use_transaction'] === true) {
            return new InMemoryTransactionalChronicler($eventStreamProvider);
        }

        $useEventDecorator = $options['use_event_decorator'] ?? false;
        $tracker = $this->determineTracker($config);

        if ($useEventDecorator === true && $tracker instanceof StreamTracker) {
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

    private function determineEventStreamProvider(array $config): EventStreamProvider
    {
        $eventStreamKey = $config['provider'] ?? null;

        $eventStream = $this->fromChronicler("provider.$eventStreamKey");

        if (!is_string($eventStream)) {
            throw new RuntimeException("Unable to determine stream provider");
        }

        return $this->app->make($eventStream);
    }

    public function determineTracker(array $config): ?StreamTracker
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
