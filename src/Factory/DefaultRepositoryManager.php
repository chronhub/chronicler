<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Factory;

use Illuminate\Support\Arr;
use Chronhub\Chronicler\Stream\StreamName;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Chronhub\Chronicler\Exception\RuntimeException;
use Chronhub\Chronicler\Aggregate\NullAggregateCache;
use Chronhub\Chronicler\Aggregate\GenericAggregateType;
use Chronhub\Chronicler\Aggregate\GenericAggregateCache;
use Chronhub\Chronicler\Aggregate\AggregateEventReleaser;
use Chronhub\Chronicler\Support\Contracts\StreamProducer;
use Chronhub\Foundation\Message\Decorator\ChainDecorators;
use Chronhub\Snapshot\Aggregate\AggregateSnapshotRepository;
use Chronhub\Chronicler\Aggregate\GenericAggregateRepository;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateType;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateCache;
use Chronhub\Chronicler\Support\Contracts\Factory\ChroniclerManager;
use Chronhub\Chronicler\Support\Contracts\Factory\RepositoryManager;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateRepository;
use function is_array;
use function is_string;
use function class_exists;
use function is_subclass_of;

final class DefaultRepositoryManager implements RepositoryManager
{
    use HasRepositoryFactory;

    private array $repositories = [];
    private array $customRepositories = [];
    private array $config;

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

        if ( ! is_array($config) || empty($config)) {
            throw new RuntimeException("Invalid repository config for stream name $streamName");
        }

        return $this->repositories[$streamName] = $this->resolveAggregateRepository($streamName, $config);
    }

    public function extends(string $streamName, callable $repository): void
    {
        $this->customRepositories[$streamName] = $repository;
    }

    private function resolveAggregateRepository(string $streamName, array $config): AggregateRepository
    {
        if ($customRepository = $this->customRepositories[$streamName] ?? null) {
            return $customRepository($this->app, $config);
        }

        $aggregateRepository = null;
        $snapshotStoreId = null;

        if ($this->isSnapshotProvided($config)) {
            $aggregateRepository = $config['snapshot']['repository'] ?? AggregateSnapshotRepository::class;
            $snapshotStoreId = $this->app->get($config['snapshot']['store']);
        }

        if (null === $aggregateRepository) {
            $aggregateRepository = GenericAggregateRepository::class;
        }

        if ( ! class_exists($aggregateRepository)) {
            throw new RuntimeException("Invalid aggregate repository class $aggregateRepository");
        }

        $aggregateType = $this->makeAggregateType($config['aggregate_type']);

        return new $aggregateRepository(
            $aggregateType,
            $this->chroniclerManager->create($config['chronicler']),
            $this->makeStreamProducer($streamName, $config),
            $this->makeAggregateCacheDriver($aggregateType->aggregateRootClassName(), $config['cache'] ?? 0),
            $this->makeAggregateEventReleaser($streamName),
            $snapshotStoreId
        );
    }

    private function makeAggregateType(string|array $aggregateType): AggregateType
    {
        if (is_string($aggregateType)) {
            if (is_subclass_of($aggregateType, AggregateRoot::class)) {
                return new GenericAggregateType($aggregateType);
            }

            return $this->app->make($aggregateType);
        }

        return new GenericAggregateType($aggregateType['root'], $aggregateType['children']);
    }

    private function makeStreamProducer(string $streamName, array $config): StreamProducer
    {
        $streamProducer = $this->determineStreamProducerDriver($config);

        if ( ! is_string($streamProducer)) {
            throw new RuntimeException('Unable to determine stream producer strategy');
        }

        return new $streamProducer(new StreamName($streamName));
    }

    private function makeAggregateCacheDriver(string $aggregateType, int $limit): AggregateCache
    {
        if (0 === $limit) {
            return new NullAggregateCache();
        }

        return new GenericAggregateCache($aggregateType, $limit);
    }

    private function makeAggregateEventReleaser(string $streamName): AggregateEventReleaser
    {
        $eventDecorators = $this->determineRepositoryEventDecorator($streamName);

        $messageDecorators = new ChainDecorators(...$eventDecorators);

        return new AggregateEventReleaser($messageDecorators);
    }

    private function isSnapshotProvided(array $config): bool
    {
        return isset($config['snapshot']) && true === ($config['snapshot']['use_snapshot'] ?? false);
    }

    private function fromChronicler(string $key): mixed
    {
        return Arr::get($this->config, $key);
    }
}
