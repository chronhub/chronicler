<?php

namespace Chronhub\Chronicler\Support\Contracts\Aggregate;

use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Countable;

interface AggregateCache extends Countable
{
    /**
     * @param AggregateId $aggregateId
     * @return AggregateRoot|null
     */
    public function get(AggregateId $aggregateId): ?AggregateRoot;

    /**
     * @param AggregateRoot $aggregateRoot
     */
    public function put(AggregateRoot $aggregateRoot): void;

    /**
     * @param AggregateId $aggregateId
     */
    public function forget(AggregateId $aggregateId): void;

    /**
     * @return bool
     */
    public function flush(): bool;

    /**
     * @param AggregateId $aggregateId
     * @return bool
     */
    public function has(AggregateId $aggregateId): bool;
}
