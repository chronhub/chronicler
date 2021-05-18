<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Loader;

use Chronhub\Chronicler\Driver\Connection\EventConverter;
use Chronhub\Chronicler\Exception\QueryFailure;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\Stream\StreamName;
use Generator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;

abstract class StreamEventLoader
{
    protected EventConverter $eventConverter;

    public function query(Builder $builder, StreamName $streamName): Generator
    {
        try {
            $streamEvents = $this->generateFrom($builder, $streamName);

            if (null === $streamEvents->current()) {
                throw StreamNotFound::withStreamName($streamName);
            }

            foreach ($streamEvents as $streamEvent) {
                yield $this->eventConverter->toDomainEvent($streamEvent);
            }

            return $streamEvents->getReturn();
        } catch (QueryException $queryException) {
            if ('00000' !== $queryException->getCode()) {
                throw StreamNotFound::withStreamName($streamName);
            }

            throw QueryFailure::fromQueryException($queryException);
        }
    }

    abstract protected function generateFrom(Builder $builder, StreamName $StreamName): Generator;
}
