<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Stream;

use Generator;
use ArrayIterator;
use Chronhub\Chronicler\Stream\Stream;
use Illuminate\Support\LazyCollection;
use Chronhub\Chronicler\Tests\TestCase;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Tests\Double\SomeCommand;
use function iterator_to_array;

/** @coversDefaultClass \Chronhub\Chronicler\Stream\Stream */
class StreamTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $streamName = new StreamName('customer');

        $stream = new Stream($streamName);

        $this->assertEquals($streamName, $stream->name());

        $events = $stream->events();

        iterator_to_array($events);

        $this->assertEquals(0, $events->getReturn());
    }

    /**
     * @test
     * @dataProvider provideIterableEvents
     */
    public function it_can_generate_events(iterable $iterable): void
    {
        $streamName = new StreamName('customer');

        $stream = new Stream($streamName, $iterable);

        $events = $stream->events();

        foreach ($events as $event) {
            $this->assertEquals($event, $iterable[0]);
        }

        $this->assertEquals(1, $events->getReturn());
    }

    /**
     * @test
     */
    public function it_return_enumerable_streams(): void
    {
        $streamName = new StreamName('customer');

        $collection = new LazyCollection();

        $stream = new Stream($streamName, $collection);

        $this->assertEquals($collection, $stream->enumerator());
    }

    /**
     * @test
     */
    public function it_return_enumerable_streams_2(): void
    {
        $streamName = new StreamName('customer');

        $stream = new Stream($streamName, []);

        $this->assertInstanceOf(LazyCollection::class, $stream->enumerator());
    }

    public function provideIterableEvents(): Generator
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        yield [[$event]];

        yield [new ArrayIterator([$event])];

        yield [[new LazyCollection([$event])]];
    }
}
