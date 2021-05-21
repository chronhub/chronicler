<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\InMemory;

use Generator;
use Illuminate\Support\Collection;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Chronicler\Support\Traits\DetectStreamCategory;
use Chronhub\Chronicler\Support\Contracts\InMemoryChronicler;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\Contracts\Model\EventStreamProvider;
use Chronhub\Chronicler\Support\Contracts\Query\InMemoryQueryFilter;
use function count;

abstract class AbstractInMemoryChronicler implements InMemoryChronicler
{
    use DetectStreamCategory;

    protected Collection $streams;

    public function __construct(protected EventStreamProvider $eventStreamProvider)
    {
        $this->streams = new Collection();
    }

    public function retrieveAll(StreamName $streamName, AggregateId $aggregateId, string $direction = 'asc'): Generator
    {
        $filter = new class($aggregateId, $direction) implements InMemoryQueryFilter {
            public function __construct(private AggregateId $aggregateId,
                                        private string $direction)
            {
            }

            public function orderBy(): string
            {
                return $this->direction;
            }

            public function filterQuery(): callable
            {
                return function (DomainEvent $event): ?DomainEvent {
                    $currentAggregateId = $event->header(Header::AGGREGATE_ID);

                    if ($currentAggregateId instanceof AggregateId) {
                        $currentAggregateId = $currentAggregateId->toString();
                    }

                    return $currentAggregateId === $this->aggregateId->toString() ? $event : null;
                };
            }
        };

        return $this->retrieveFiltered($streamName, $filter);
    }

    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        if ( ! $queryFilter instanceof InMemoryQueryFilter) {
            throw new InvalidArgumentException('Query filter must implements ' . InMemoryQueryFilter::class);
        }

        return $this->filterEvents($streamName, $queryFilter);
    }

    public function delete(StreamName $streamName): void
    {
        if ( ! $this->hasStream($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }

        $this->eventStreamProvider->deleteStream($streamName->toString());

        $this->streams->forget($streamName->toString());
    }

    public function fetchStreamNames(StreamName ...$streamNames): array
    {
        return $this->eventStreamProvider->filterByStreams($streamNames);
    }

    public function fetchCategoryNames(string ...$categoryNames): array
    {
        return $this->eventStreamProvider->filterByCategories($categoryNames);
    }

    public function hasStream(StreamName $streamName): bool
    {
        return $this->eventStreamProvider->hasRealStreamName($streamName->toString());
    }

    public function streams(): Collection
    {
        return $this->streams;
    }

    protected function filterEvents(StreamName $streamName,
                                    InMemoryQueryFilter $filter): Generator
    {
        if ( ! $this->hasStream($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }

        $events = (new Collection($this->streams->get($streamName->toString())))
            ->sortBy(function (DomainEvent $event): int {
                return $event->header(Header::AGGREGATE_VERSION);
            }, SORT_NUMERIC, 'desc' === $filter->orderBy())
            ->filter($filter->filterQuery());

        if ($events->isEmpty()) {
            throw StreamNotFound::withStreamName($streamName);
        }

        yield from $events;

        return count($events);
    }

    protected function decorateEventWithInternalPosition(array $events): array
    {
        foreach ($events as &$event) {
            $internalPosition = Header::INTERNAL_POSITION;

            if ($event->hasNot($internalPosition)) {
                $event = $event->withHeader($internalPosition, $event->header(Header::AGGREGATE_VERSION));
            }
        }

        return $events;
    }
}
