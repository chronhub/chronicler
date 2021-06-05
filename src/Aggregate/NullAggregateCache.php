<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Aggregate;

use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateCache;

final class NullAggregateCache implements AggregateCache
{
    public function get(AggregateId $aggregateId): ?AggregateRoot
    {
        return null;
    }

    public function put(AggregateRoot $aggregateRoot): void
    {
    }

    public function forget(AggregateId $aggregateId): void
    {
    }

    public function flush(): bool
    {
        return true;
    }

    public function has(AggregateId $aggregateId): bool
    {
        return false;
    }

    public function count()
    {
        return 0;
    }
}
