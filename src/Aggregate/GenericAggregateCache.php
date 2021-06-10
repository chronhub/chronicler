<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Aggregate;

use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateCache;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Illuminate\Support\Facades\Cache;

final class GenericAggregateCache implements AggregateCache
{
    private int $count = 0;

    public function __construct(string $aggregateType,
                                private string $cacheTag,
                                private int $limit = 10000)
    {
    }

    public function put(AggregateRoot $aggregateRoot): void
    {
        if ($this->count === $this->limit) {
            $this->flush();
        }

        $aggregateId = $aggregateRoot->aggregateId();

        if (!$this->has($aggregateId)) {
            ++$this->count;
        }

        $cacheKey = $this->determineCacheKey($aggregateId);

        Cache::tags([$this->cacheTag])->forever($cacheKey, $aggregateRoot);
    }

    public function get(AggregateId $aggregateId): ?AggregateRoot
    {
        $cacheKey = $this->determineCacheKey($aggregateId);

        return Cache::tags([$this->cacheTag])->get($cacheKey);
    }

    public function forget(AggregateId $aggregateId): void
    {
        if ($this->has($aggregateId)) {
            $cacheKey = $this->determineCacheKey($aggregateId);

            if (Cache::tags([$this->cacheTag])->forget($cacheKey)) {
                --$this->count;
            }
        }
    }

    public function flush(): bool
    {
        $this->count = 0;

        return Cache::tags([$this->cacheTag])->flush();
    }

    public function has(AggregateId $aggregateId): bool
    {
        $cacheKey = $this->determineCacheKey($aggregateId);

        return Cache::tags([$this->cacheTag])->has($cacheKey);
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
