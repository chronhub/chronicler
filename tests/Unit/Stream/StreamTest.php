<?php

namespace Chronhub\Chronicler\Tests\Unit\Stream;

use ArrayIterator;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Tests\Double\SomeCommand;
use Chronhub\Chronicler\Tests\TestCase;
use Generator;
use Illuminate\Support\LazyCollection;

/** @coversDefaultClass \Chronhub\Chronicler\Stream\Stream */
class StreamTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $streamName = new StreamName('customer_stream');

        $stream = new Stream($streamName);

        $this->assertEquals($streamName, $stream->name());

        $events = $stream->events();

        iterator_to_array($events);

        $this->assertEquals(0, $events->getReturn());
    }

    /**
     * @test
     * @param iterable $iterable
     * @dataProvider provideIterableEvents
     */
    public function it_can_generate_events(iterable $iterable): void
    {
        $streamName = new StreamName('customer_stream');

        $stream = new Stream($streamName, $iterable);

        $events = $stream->events();

        foreach ($events as $event) {
            $this->assertEquals($event, $iterable[0]);
        }

        $this->assertEquals(1, $events->getReturn());
    }

    public function provideIterableEvents(): Generator
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        yield [[$event]];

        yield [new ArrayIterator([$event])];

        yield [[new LazyCollection([$event])]];
    }
}
