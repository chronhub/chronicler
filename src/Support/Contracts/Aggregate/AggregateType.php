<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Aggregate;

use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;

interface AggregateType
{
    public function determineFromEvent(DomainEvent $event): string;

    public function determineFromAggregateRoot(AggregateRoot $aggregateRoot): string;

    public function assertAggregateRootIsSupported(string $aggregateRoot): void;

    public function aggregateRootClassName(): string;
}
