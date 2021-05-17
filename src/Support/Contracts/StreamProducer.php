<?php

namespace Chronhub\Chronicler\Support\Contracts;

use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

interface StreamProducer
{
    /**
     * @param string $aggregateId
     * @return StreamName
     */
    public function determineStreamName(string $aggregateId): StreamName;

    /**
     * @param AggregateId $aggregateId
     * @param iterable    $events
     * @return Stream
     */
    public function produceStream(AggregateId $aggregateId, iterable $events): Stream;

    /**
     * @param DomainEvent $firstEvent
     * @return bool
     */
    public function isFirstCommit(DomainEvent $firstEvent): bool;

    /**
     * @return bool
     */
    public function isOneStreamPerAggregate(): bool;
}
