<?php

declare(strict_types=1);

namespace Chronhub\Chronicler;

use Chronhub\Chronicler\Stream\Stream;
use Illuminate\Database\QueryException;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Exception\QueryFailure;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\Exception\ConcurrencyException;
use Chronhub\Chronicler\Support\Traits\HasConnectionTransaction;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;
use Chronhub\Chronicler\Driver\Connection\AbstractChroniclerConnection;

final class PgsqlChronicler extends AbstractChroniclerConnection implements TransactionalChronicler
{
    use HasConnectionTransaction;

    public function persistFirstCommit(Stream $stream): void
    {
        $streamName = $stream->name();

        $tableName = $this->persistenceStrategy->tableName($streamName);

        $this->createEventStream($streamName, $tableName);

        $this->upStreamTable($streamName, $tableName);

        $this->persist($stream);
    }

    public function persist(Stream $stream): void
    {
        $streamEvents = $stream->enumerator();

        if ($streamEvents->isEmpty()) {
            return;
        }

        $streamName = $stream->name();

        $tableName = $this->persistenceStrategy->tableName($streamName);

        if ( ! $this->writeLockStrategy->acquireLock($tableName)) {
            throw ConcurrencyException::failedToAcquireLock();
        }

        try {
            $this
                ->queryBuilder($streamName)
                ->insert($this->serializeStreamEvents($streamEvents));
        } catch (QueryException $queryException) {
            match ($queryException->getCode()) {
                '42P01' => throw StreamNotFound::withStreamName($streamName), '23000', '23505' => throw ConcurrencyException::fromUnlockStreamFailure($queryException), default => throw QueryFailure::fromQueryException($queryException)
            };
        }

        $this->writeLockStrategy->releaseLock($tableName);
    }

    public function delete(StreamName $streamName): void
    {
        try {
            $result = $this->eventStreamProvider->deleteStream($streamName->toString());

            if ( ! $result) {
                throw StreamNotFound::withStreamName($streamName);
            }
        } catch (QueryException $exception) {
            if ('00000' !== $exception->getCode()) {
                throw QueryFailure::fromQueryException($exception);
            }
        }

        $tableName = $this->persistenceStrategy->tableName($streamName);

        try {
            $this->connection->getSchemaBuilder()->drop($tableName);
        } catch (QueryException $exception) {
            if ('00000' !== $exception->getCode()) {
                throw QueryFailure::fromQueryException($exception);
            }
        }
    }
}
