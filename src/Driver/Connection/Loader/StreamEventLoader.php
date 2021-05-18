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
use Illuminate\Support\Enumerable;
use stdClass;

abstract class StreamEventLoader
{
    protected EventConverter $eventConverter;

    public function query(Builder $builder, StreamName $streamName): Generator
    {
        try {
            return yield from $this->fromCollection($builder, $streamName)
                ->map(function (stdClass $payload) {
                    return $this->eventConverter->toDomainEvent($payload);
                });
        } catch (QueryException $queryException) {
            if ('00000' !== $queryException->getCode()) {
                throw StreamNotFound::withStreamName($streamName);
            }

            throw QueryFailure::fromQueryException($queryException);
        }
    }

    abstract protected function fromCollection(Builder $builder, StreamName $StreamName): Enumerable;
}
