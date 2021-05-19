<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Aggregate;

use Chronhub\Chronicler\Support\Contracts\ReadOnlyChronicler;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;

interface AggregateRepository
{
    public function retrieve(AggregateId $aggregateId): ?AggregateRoot;

    public function persist(AggregateRoot $aggregateRoot): void;

    public function chronicler(): ReadOnlyChronicler;

    /**
     * Flush aggregate cache.
     */
    public function flushCache(): void;
}
