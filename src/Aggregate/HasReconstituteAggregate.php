<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Aggregate;

use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateType;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Chronicler\Support\Contracts\StreamProducer;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Generator;

trait HasReconstituteAggregate
{
    protected AggregateType $aggregateType;
    protected StreamProducer $streamProducer;
    protected Chronicler $chronicler;

    protected function reconstituteAggregateRoot(AggregateId $aggregateId): ?AggregateRoot
    {
        try {
            $history = $this->fromHistory($aggregateId, null);

            $firstEvent = $history->current();

            /** @var AggregateRoot&static $aggregateRoot */
            $aggregateRoot = $this->aggregateType->determineFromEvent($firstEvent);

            return $aggregateRoot::reconstituteFromEvents($aggregateId, $history);
        } catch (StreamNotFound) {
            return null;
        }
    }

    /**
     * @param AggregateId      $aggregateId
     * @param QueryFilter|null $queryFilter
     * @return Generator<DomainEvent>
     */
    protected function fromHistory(AggregateId $aggregateId, ?QueryFilter $queryFilter): Generator
    {
        $streamName = $this->streamProducer->determineStreamName($aggregateId->toString());

        // todo test query filter with snapshot
        yield from $events = $queryFilter instanceof QueryFilter
            ? $this->chronicler->retrieveFiltered($streamName, $queryFilter)
            : $this->chronicler->retrieveAll($streamName, $aggregateId);

        return (int)$events->getReturn();
    }
}
