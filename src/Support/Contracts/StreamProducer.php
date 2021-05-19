<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts;

use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

interface StreamProducer
{
    public function determineStreamName(string $aggregateId): StreamName;

    public function produceStream(AggregateId $aggregateId, iterable $events): Stream;

    public function isFirstCommit(DomainEvent $firstEvent): bool;

    public function isOneStreamPerAggregate(): bool;
}
