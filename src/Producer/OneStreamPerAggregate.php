<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Producer;

use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Contracts\StreamProducer;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Message\Header;

final class OneStreamPerAggregate implements StreamProducer
{
    public function __construct(private StreamName $streamName)
    {
        //
    }

    public function determineStreamName(string $aggregateId): StreamName
    {
        return new StreamName($this->streamName->toString() . '-' . $aggregateId);
    }

    public function produceStream(AggregateId $aggregateId, iterable $events): Stream
    {
        $streamName = $this->determineStreamName($aggregateId->toString());

        return new Stream($streamName, $events);
    }

    public function isFirstCommit(DomainEvent $firstEvent): bool
    {
        return $firstEvent->header(Header::AGGREGATE_VERSION) === 1;
    }

    public function isOneStreamPerAggregate(): bool
    {
        return true;
    }
}
