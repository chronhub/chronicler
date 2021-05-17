<?php

namespace Chronhub\Chronicler\Support\Contracts\Aggregate;

use Chronhub\Foundation\Message\DomainEvent;

interface AggregateType
{
    /**
     * @param DomainEvent $event
     * @return string
     */
    public function determineFromEvent(DomainEvent $event): string;

    /**
     * @param string $aggregateRoot
     */
    public function assertAggregateRootIsSupported(string $aggregateRoot): void;

    /**
     * @return string
     */
    public function aggregateRootClassName(): string;
}
