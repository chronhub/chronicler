<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection;

use Generator;
use Illuminate\Support\Enumerable;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Message\DomainEvent;
use Illuminate\Database\ConnectionInterface;
use Chronhub\Chronicler\Exception\QueryFailure;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\Exception\StreamAlreadyExists;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Chronicler\Support\Contracts\StreamPersistence;
use Chronhub\Chronicler\Support\Contracts\WriteLockStrategy;
use Chronhub\Chronicler\Support\Traits\DetectStreamCategory;
use Chronhub\Chronicler\Support\Contracts\ChroniclerConnection;
use Chronhub\Chronicler\Driver\Connection\WriteLock\NoWriteLock;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;
use Chronhub\Chronicler\Driver\Connection\Loader\StreamEventLoader;
use Chronhub\Chronicler\Support\Contracts\Model\EventStreamProvider;
use function array_map;

abstract class AbstractChroniclerConnection implements ChroniclerConnection
{
    use DetectStreamCategory;

    public function __construct(protected ConnectionInterface|Connection $connection,
                                protected EventStreamProvider $eventStreamProvider,
                                protected StreamPersistence $persistenceStrategy,
                                protected StreamEventLoader $streamEventLoader,
                                protected ?WriteLockStrategy $writeLockStrategy)
    {
        $this->writeLockStrategy = $writeLockStrategy ?? new NoWriteLock();
    }

    public function retrieveAll(StreamName $streamName, AggregateId $aggregateId, string $direction = 'asc'): Generator
    {
        $query = $this->queryBuilder($streamName);

        if ( ! $this->persistenceStrategy->isOneStreamPerAggregate()) {
            $query = $query->whereJsonContains('headers->__aggregate_id', $aggregateId->toString());
        }

        $query = $query->orderBy('no', $direction);

        try {
            return yield from $this->streamEventLoader->query($query, $streamName);
        } catch (StreamNotFound $exception) {
            $this->handleStreamNotFound($exception);
        }
    }

    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        $builder = $this->queryBuilder($streamName);

        $queryFilter->filterQuery()($builder);

        try {
            return yield from $this->streamEventLoader->query($builder, $streamName);
        } catch (StreamNotFound $exception) {
            $this->handleStreamNotFound($exception);
        }
    }

    public function fetchStreamNames(StreamName ...$streamNames): array
    {
        $streamNames = array_map(
            fn (StreamName $streamName): string => $streamName->toString(), $streamNames
        );

        return array_map(
            fn (string $streamName): StreamName => new StreamName($streamName),
            $this->eventStreamProvider->filterByStreams($streamNames)
        );
    }

    public function fetchCategoryNames(string ...$categoryNames): array
    {
        return $this->eventStreamProvider->filterByCategories($categoryNames);
    }

    public function hasStream(StreamName $streamName): bool
    {
        return $this->eventStreamProvider->hasRealStreamName($streamName->toString());
    }

    protected function createEventStream(StreamName $streamName, string $tableName): void
    {
        try {
            $category = $this->detectStreamCategory($streamName->toString());

            $result = $this->eventStreamProvider->createStream($streamName->toString(), $tableName, $category);

            if ( ! $result) {
                throw new QueryFailure("Unable to insert data in $tableName event stream table");
            }
        } catch (QueryException $exception) {
            match ($exception->getCode()) {
                '23000', '23505' => throw StreamAlreadyExists::withStreamName($streamName), default => throw QueryFailure::fromQueryException($exception)
            };
        }
    }

    protected function upStreamTable(StreamName $streamName, string $tableName): void
    {
        try {
            $this->persistenceStrategy->up($tableName);
        } catch (QueryException $exception) {
            $this->connection->getSchemaBuilder()->drop($tableName);

            $this->eventStreamProvider->deleteStream($streamName->toString());

            throw $exception;
        }
    }

    protected function serializeStreamEvents(Enumerable $streamEvents): array
    {
        return $streamEvents->map(
            fn (DomainEvent $event): array => $this->persistenceStrategy->serializeMessage($event)
        )->toArray();
    }

    protected function handleStreamNotFound(StreamNotFound $exception): void
    {
        // with write lock strategy set to false
        // any queries will raise exception: In failed sql transaction: PDOException: SQLSTATE[25P02]:
        if ($this->writeLockStrategy instanceof NoWriteLock
            && $this->persistenceStrategy->isOneStreamPerAggregate()
            && $this instanceof TransactionalChronicler
            && $this->connection->transactionLevel() > 0
        ) {
            $this->connection->rollBack();
        }

        throw $exception;
    }

    protected function queryBuilder(StreamName $streamName): Builder
    {
        $tableName = $this->persistenceStrategy->tableName($streamName);

        return $this->connection->table($tableName);
    }
}
