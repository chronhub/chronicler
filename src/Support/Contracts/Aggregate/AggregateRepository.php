<?php

namespace Chronhub\Chronicler\Support\Contracts\Aggregate;

use Chronhub\Chronicler\Support\Contracts\ReadOnlyChronicler;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;

interface AggregateRepository
{
    /**
     * @param AggregateId $aggregateId
     * @return AggregateRoot|null
     */
    public function retrieve(AggregateId $aggregateId): ?AggregateRoot;

    /**
     * @param AggregateRoot $aggregateRoot
     */
    public function persist(AggregateRoot $aggregateRoot): void;

    /**
     * @return ReadOnlyChronicler
     */
    public function chronicler(): ReadOnlyChronicler;

    /**
     * Flush aggregate cache
     */
    public function flushCache(): void;
}
