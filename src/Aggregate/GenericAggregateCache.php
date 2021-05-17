<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Aggregate;

use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateCache;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Illuminate\Contracts\Cache\Store;

final class GenericAggregateCache implements AggregateCache
{
    private int $count = 0;

    public function __construct(private Store $store, private int $limit = 10000)
    {
        //
    }

    public function put(AggregateRoot $aggregateRoot): void
    {
        if ($this->count === $this->limit) {
            $this->flush();
        }

        $aggregateId = $aggregateRoot->aggregateId();

        if (!$this->has($aggregateId)) {
            $this->count++;
        }

        $cacheKey = $this->determineCacheKey($aggregateId);

        $this->store->forever($cacheKey, $aggregateRoot);
    }

    public function get(AggregateId $aggregateId): ?AggregateRoot
    {
        $cacheKey = $this->determineCacheKey($aggregateId);

        return $this->store->get($cacheKey);
    }

    public function forget(AggregateId $aggregateId): void
    {
        if ($this->has($aggregateId)) {
            $cacheKey = $this->determineCacheKey($aggregateId);

            if ($this->store->forget($cacheKey)) {
                $this->count--;
            }
        }
    }

    public function flush(): bool
    {
        $this->count = 0;

        return $this->store->flush();
    }

    public function has(AggregateId $aggregateId): bool
    {
        $cacheKey = $this->determineCacheKey($aggregateId);

        return null !== $this->store->get($cacheKey);
    }

    public function count(): int
    {
        return $this->count;
    }

    private function determineCacheKey(AggregateId $aggregateId): string
    {
        return class_basename($aggregateId::class) . ':' . $aggregateId->toString();
    }
}
