<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Aggregate;

use Countable;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;

interface AggregateCache extends Countable
{
    public function get(AggregateId $aggregateId): ?AggregateRoot;

    public function put(AggregateRoot $aggregateRoot): void;

    public function forget(AggregateId $aggregateId): void;

    public function flush(): bool;

    public function has(AggregateId $aggregateId): bool;
}
