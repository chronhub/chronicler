<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Loader;

use Generator;
use Illuminate\Support\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\LazyCollection;
use Illuminate\Database\QueryException;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Exception\QueryFailure;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\Driver\Connection\EventConverter;

abstract class StreamEventLoader
{
    protected EventConverter $eventConverter;

    public function query(Builder $builder, StreamName $streamName): Generator
    {
        // A stream not found exception is raised, when a stream name
        // which part of the table name, raise a query exception
        // and when stream events are empty

        try {
            $streamEvents = $this->generateFrom($builder, $streamName);

            $count = 0;

            foreach ($streamEvents as $streamEvent) {
                yield $this->eventConverter->toDomainEvent($streamEvent);

                ++$count;
            }

            if (0 === $count) {
                throw StreamNotFound::withStreamName($streamName);
            }

            return $count;
        } catch (QueryException $queryException) {
            if ('00000' !== $queryException->getCode()) {
                throw StreamNotFound::withStreamName($streamName);
            }

            throw QueryFailure::fromQueryException($queryException);
        }
    }

    abstract protected function generateFrom(Builder $builder, StreamName $StreamName): LazyCollection|Collection;
}
