<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Driver\Connection\Loader;

use stdClass;
use Prophecy\Prophecy\ObjectProphecy;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\LazyCollection;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Chronhub\Chronicler\Tests\Double\SomeDomainEvent;
use Chronhub\Chronicler\Driver\Connection\EventConverter;
use Chronhub\Chronicler\Driver\Connection\Loader\CursorQueryLoader;

final class CursorQueryLoaderTest extends TestCaseWithProphecy
{
    private Builder|ObjectProphecy $builder;
    private EventConverter|ObjectProphecy $eventConverter;
    private StreamName $streamName;

    protected function setUp(): void
    {
        $this->builder = $this->prophesize(Builder::class);
        $this->eventConverter = $this->prophesize(EventConverter::class);
        $this->streamName = new StreamName('customer');
    }

    /**
     * @test
     */
    public function it_generate_events(): void
    {
        $event = new stdClass();
        $event->headers = [];
        $event->content = [];
        $event->no = 1;

        $this->builder->cursor()->willReturn(new LazyCollection([$event]))->shouldBeCalled();

        $expectedEvent = SomeDomainEvent::fromContent(['steph' => 'bug']);
        $this->eventConverter->toDomainEvent($event)->willReturn($expectedEvent)->shouldBeCalled();

        $loader = new CursorQueryLoader($this->eventConverter->reveal());

        $eventsLoaded = $loader->query($this->builder->reveal(), $this->streamName);

        $eventLoaded = null;
        foreach ($eventsLoaded as $_eventLoaded) {
            $eventLoaded = $_eventLoaded;
        }

        $this->assertEquals($expectedEvent, $eventLoaded);
        $this->assertEquals(1, $eventsLoaded->getReturn());
    }
}
