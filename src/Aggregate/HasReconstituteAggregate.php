<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Aggregate;

use Generator;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Support\Contracts\StreamProducer;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateType;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;

trait HasReconstituteAggregate
{
    protected AggregateType $aggregateType;
    protected StreamProducer $streamProducer;
    protected Chronicler $chronicler;

    protected function reconstituteAggregateRoot(AggregateId $aggregateId, ?QueryFilter $queryFilter = null): ?AggregateRoot
    {
        try {
            $history = $this->fromHistory($aggregateId, $queryFilter);

            if ( ! $history->valid()) {
                return null;
            }

            /** @var AggregateRoot&static $aggregateRoot */
            $aggregateRoot = $this->aggregateType->determineFromEvent($history->current());

            return $aggregateRoot::reconstituteFromEvents($aggregateId, $history);
        } catch (StreamNotFound) {
            return null;
        }
    }

    /**
     * @return Generator<DomainEvent>
     */
    protected function fromHistory(AggregateId $aggregateId, ?QueryFilter $queryFilter): Generator
    {
        $streamName = $this->streamProducer->determineStreamName($aggregateId->toString());

        return yield from $queryFilter instanceof QueryFilter
            ? $this->chronicler->retrieveFiltered($streamName, $queryFilter)
            : $this->chronicler->retrieveAll($streamName, $aggregateId);
    }
}
