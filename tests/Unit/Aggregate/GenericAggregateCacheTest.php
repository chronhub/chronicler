<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Aggregate;

use Chronhub\Chronicler\Aggregate\GenericAggregateCache;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Chronhub\Foundation\Aggregate\GenericAggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Generator;
use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Store;

/** @coversDefaultClass \Chronhub\Chronicler\Aggregate\GenericAggregateCache */
final class GenericAggregateCacheTest extends TestCaseWithProphecy
{
    private Store $store;

    protected function setUp(): void
    {
        $this->store = new ArrayStore();
    }

    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $cache = new GenericAggregateCache($this->store, 3);

        $aggregateId = GenericAggregateId::create();

        $this->assertCount(0, $cache);
        $this->assertNull($cache->get($aggregateId));
        $this->assertFalse($cache->has($aggregateId));
    }

    /**
     * @test
     */
    public function it_can_cache_and_retrieve_aggregate(): void
    {
        $cache = new GenericAggregateCache($this->store, 5);

        foreach ($this->provideAggregate(5) as [$aggregateId, $aggregateRoot]) {
            $cache->put($aggregateRoot->reveal());

            $this->assertEquals($aggregateRoot->reveal(), $cache->get($aggregateId));
        }

        $this->assertEquals($aggregateRoot->reveal(), $cache->get($aggregateId));
        $this->assertCount(5, $cache);
    }

    /**
     * @test
     */
    public function it_forget_aggregate(): void
    {
        $cache = new GenericAggregateCache($this->store, 1000);

        foreach ($this->provideAggregate(2) as [$aggregateId, $aggregateRoot]) {
            $cache->put($aggregateRoot->reveal());

            $cache->forget($aggregateId);
        }

        $this->assertCount(0, $cache);
    }

    /**
     * @test
     */
    public function it_flush_cache(): void
    {
        $cache = new GenericAggregateCache($this->store, 1000);

        foreach ($this->provideAggregate(5) as [$aggregateId, $aggregateRoot]) {
            $cache->put($aggregateRoot->reveal());
        }

        $this->assertCount(5, $cache);

        $cache->flush();

        $this->assertCount(0, $cache);
    }

    /**
     * @test
     */
    public function it_assert_cache_key_in_store(): void
    {
        $cache = new GenericAggregateCache($this->store, 3);

        [$aggregateId, $aggregateRoot] = $this->provideAggregate(1)->current();

        $cache->put($aggregateRoot->reveal());

        $cacheKey = class_basename($aggregateId) . ':' . $aggregateId->toString();

        $this->assertEquals($aggregateRoot->reveal(), $this->store->get($cacheKey));
    }

    /**
     * @test
     */
    public function it_flush_cache_when_limit_is_reached(): void
    {
        $cache = new GenericAggregateCache($this->store, 2);

        foreach ($this->provideAggregate(3) as [$aggregateId, $aggregateRoot]) {
            $cache->put($aggregateRoot->reveal());
        }

        $this->assertEquals(1, $cache->count());
    }

    private function provideAggregate(int $limit): Generator
    {
        while ($limit !== 0) {
            $aggregateId = GenericAggregateId::create();

            $aggregateRoot = $this->prophesize(AggregateRoot::class);
            $aggregateRoot->aggregateId()->willReturn($aggregateId)->shouldBeCalled();

            yield [$aggregateId, $aggregateRoot];

            $limit--;
        }
    }
}
