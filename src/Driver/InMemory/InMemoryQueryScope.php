<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\InMemory;

use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\Support\Contracts\Query\QueryScope;
use Chronhub\Chronicler\Support\Contracts\Query\InMemoryQueryFilter;

class InMemoryQueryScope implements QueryScope
{
    public function fromToPosition(int $from, int $to, string $direction = 'asc'): InMemoryQueryFilter
    {
        if ($from <= 0) {
            throw new InvalidArgumentException('From position must be greater or equal than 0');
        }

        if ($to <= $from) {
            throw new InvalidArgumentException('To position must be greater than from position');
        }

        $callback = function (DomainEvent $message) use ($from, $to): ?DomainEvent {
            $position = $message->header(Header::INTERNAL_POSITION);

            return $position >= $from && $position <= $to ? $message : null;
        };

        return $this->wrap($callback, $direction);
    }

    public function matchAggregateGreaterThanVersion(string $aggregateId,
                                                     string $aggregateType,
                                                     int $aggregateVersion,
                                                     string $direction = 'asc'): InMemoryQueryFilter
    {
        $callback = function (DomainEvent $event) use ($aggregateId, $aggregateType, $aggregateVersion): ?DomainEvent {
            $currentAggregateId = (string) $event->header(Header::AGGREGATE_ID);

            if ($currentAggregateId !== $aggregateId) {
                return null;
            }

            if ($event->header(Header::AGGREGATE_TYPE) !== $aggregateType) {
                return null;
            }

            return $event->header(Header::INTERNAL_POSITION) > $aggregateVersion ? $event : null;
        };

        return $this->wrap($callback, $direction);
    }

    public function wrap(callable $query, string $direction = 'asc'): InMemoryQueryFilter
    {
        return new class($query, $direction) implements InMemoryQueryFilter {
            /**
             * @var callable
             */
            private $query;

            public function __construct($query, private string $direction)
            {
                $this->query = $query;
            }

            public function filterQuery(): callable
            {
                return $this->query;
            }

            public function orderBy(): string
            {
                return $this->direction;
            }
        };
    }
}
