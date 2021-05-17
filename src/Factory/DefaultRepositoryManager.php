<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Factory;

use Chronhub\Chronicler\Aggregate\AggregateEventReleaser;
use Chronhub\Chronicler\Aggregate\GenericAggregateCache;
use Chronhub\Chronicler\Aggregate\GenericAggregateRepository;
use Chronhub\Chronicler\Aggregate\GenericAggregateType;
use Chronhub\Chronicler\Exception\RuntimeException;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateCache;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateRepository;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateType;
use Chronhub\Chronicler\Support\Contracts\Factory\ChroniclerManager;
use Chronhub\Chronicler\Support\Contracts\Factory\RepositoryManager;
use Chronhub\Chronicler\Support\Contracts\StreamProducer;
use Chronhub\Foundation\Message\Decorator\ChainDecorators;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Chronhub\Foundation\Support\Contracts\Message\MessageDecorator;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use function is_string;

final class DefaultRepositoryManager implements RepositoryManager
{
    protected array $repositories = [];
    protected array $customRepositories = [];
    protected array $config;

    public function __construct(private Application $app, private ChroniclerManager $chroniclerManager)
    {
        $this->config = $app->get(Repository::class)->get('chronicler');
    }

    public function create(string $streamName): AggregateRepository
    {
        if ($repository = $this->repositories[$streamName] ?? null) {
            return $repository;
        }

        $config = $this->fromChronicler("repositories.$streamName");

        if (!is_array($config) || empty($config)) {
            throw new RuntimeException("Invalid repository config for stream name $streamName");
        }

        return $this->repositories[$streamName] = $this->resolveAggregateRepository($streamName, $config);
    }

    private function resolveAggregateRepository(string $streamName, array $config): AggregateRepository
    {
        if ($customRepository = $this->customRepositories[$streamName] ?? null) {
            return $customRepository($this->app, $config);
        }

        $aggregateRepository = null;
        $snapshotStoreId = null;

        // $this->isSnapshotProvided($config);

        if (null === $aggregateRepository) {
            $aggregateRepository = GenericAggregateRepository::class;
        }

        if (!class_exists($aggregateRepository)) {
            throw new RuntimeException("Invalid aggregate repository class $aggregateRepository");
        }

        $eventBuilder = new AggregateEventReleaser($this->createChainEventDecorator($streamName));

        return new $aggregateRepository(
            $this->determineAggregateType($config['aggregate_type']),
            $this->chroniclerManager->create($config['chronicler']),
            $this->determineStreamProducer($streamName, $config),
            $this->createAggregateCacheDriver($config['cache'] ?? []),
            $eventBuilder,
            $snapshotStoreId
        );
    }

    private function determineAggregateType(string|array $aggregateType): AggregateType
    {
        if (is_string($aggregateType)) {
            if (is_subclass_of($aggregateType, AggregateRoot::class)) {
                return new GenericAggregateType($aggregateType);
            }

            return $this->app->make($aggregateType);
        }

        return new GenericAggregateType($aggregateType['root'], $aggregateType['children']);
    }

    private function determineStreamProducer(string $streamName, array $config): StreamProducer
    {
        $connection = $this->fromChronicler('connections.' . $config['chronicler']);

        if ($connection === 'default') {
            $connection = $this->fromChronicler('connections.default');
        }

        $strategy = $this->fromChronicler("connections.$connection.strategy");

        // todo handler strategy as service
        // as we can not handle service otb cause of stream name
        // if $this->app->bound($strategy)

        if ($strategy === 'default') {
            $strategy = $this->fromChronicler("strategy.default") ?? null;
        }

        $streamProducer = $this->fromChronicler("strategy.$strategy.producer");

        if (!is_string($streamProducer)) {
            throw new RuntimeException("Unable to determine stream producer strategy");
        }

        return new $streamProducer(new StreamName($streamName));
    }

    private function createAggregateCacheDriver(array $cache): AggregateCache
    {
        $driver = $cache['driver'] ?? 'null';
        $maxBeforeFlushingCache = $cache['max'] ?? 0;

        /** @var Store $store */
        $store = $this->app->get(Factory::class)->store($driver)->getStore();

        return new GenericAggregateCache($store, $maxBeforeFlushingCache);
    }

    private function createChainEventDecorator(string $streamName): MessageDecorator
    {
        $messageDecorators = [];

        if (true === $this->fromChronicler('use_foundation_decorators') ?? false) {
            $messageDecorators = $this->app['config']->get('reporter.messaging.decorators', []);
        }

        $eventDecorators = array_map(
            fn(string $decorator) => $this->app->make($decorator),
            array_merge(
                $messageDecorators,
                $this->fromChronicler("event_decorators") ?? [],
                $this->fromChronicler("repositories.$streamName.event_decorators") ?? []
            )
        );

        return new ChainDecorators(...$eventDecorators);
    }

    private function isSnapshotProvided(array $config): bool
    {
        return isset($config['snapshot']) && true === ($config['snapshot']['use_snapshot'] ?? false);
    }

    public function extends(string $streamName, callable $repository): void
    {
        $this->customRepositories[$streamName] = $repository;
    }

    private function fromChronicler(string $key): mixed
    {
        return Arr::get($this->config, $key);
    }
}
