<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Producer;

use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Chronicler\Support\Contracts\StreamProducer;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

final class SingleStreamPerAggregate implements StreamProducer
{
    public function __construct(private StreamName $streamName)
    {
    }

    public function determineStreamName(string $aggregateId): StreamName
    {
        return $this->streamName;
    }

    public function produceStream(AggregateId $aggregateId, iterable $events): Stream
    {
        $streamName = $this->determineStreamName($aggregateId->toString());

        return new Stream($streamName, $events);
    }

    public function isFirstCommit(DomainEvent $firstEvent): bool
    {
        return false;
    }

    public function isOneStreamPerAggregate(): bool
    {
        return false;
    }
}
