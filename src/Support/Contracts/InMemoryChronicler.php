<?php

namespace Chronhub\Chronicler\Support\Contracts;

use Chronhub\Chronicler\Tracking\Subscribers\PublishEvents;
use Chronhub\Chronicler\Tracking\Subscribers\PublishTransactionalInMemoryEvents;
use Chronhub\Foundation\Message\DomainEvent;

interface InMemoryChronicler extends Chronicler
{
    /**
     * Pull recorded streams
     *
     * it must only been called for standalone in memory event store
     *
     * for standalone non transactional, dev should call it manually
     * for standalone transactional, dev would use a message subscriber
     *
     * when decorated in memory with event and/or transaction,
     * dev would use a stream subscriber
     *
     * @return array<DomainEvent>
     * @see PublishTransactionalInMemoryEvents
     * @see PublishEvents
     *
     */
    public function pullCachedRecordedEvents(): array;
}
