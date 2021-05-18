<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Persistence;

use Chronhub\Chronicler\Driver\Connection\EventConverter;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Contracts\StreamPersistence;
use Chronhub\Foundation\Message\DomainEvent;

abstract class AbstractSingleStreamPersistence implements StreamPersistence
{
    public function __construct(private EventConverter $eventConverter)
    {
    }

    public function tableName(StreamName $streamName): string
    {
        return '_' . sha1($streamName->toString());
    }

    public function serializeMessage(DomainEvent $event): array
    {
        return $this->eventConverter->toArray($event, true);
    }

    public function isOneStreamPerAggregate(): bool
    {
        return false;
    }
}
