<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Driver\Connection\Loader;

use Chronhub\Chronicler\Driver\Connection\EventConverter;
use Chronhub\Chronicler\Driver\Connection\Loader\StreamEventLoader;
use Chronhub\Chronicler\Exception\QueryFailure;
use Chronhub\Chronicler\Exception\RuntimeException;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Tests\Double\SomeDomainEvent;
use Chronhub\Chronicler\Tests\Double\SomeQueryException;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use stdClass;

/** @coversDefaultClass \Chronhub\Chronicler\Driver\Connection\Loader\StreamEventLoader */
final class StreamEventLoaderTest extends TestCaseWithProphecy
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

        $loader = $this->newLoader();

        $eventsLoaded = $loader->query($this->builder->reveal(), $this->streamName);

        $eventLoaded = null;
        foreach ($eventsLoaded as $_eventLoaded) {
            $eventLoaded = $_eventLoaded;
        }

        $this->assertEquals($expectedEvent, $eventLoaded);
        $this->assertEquals(1, $eventsLoaded->getReturn());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_stream_name_is_not_found(): void
    {
        $this->expectException(StreamNotFound::class);
        $this->expectExceptionMessage('Stream customer not found');

        $exception = new RuntimeException('any exception', 23000);
        $queryException = new SomeQueryException($exception);

        $this->builder->cursor()->willThrow($queryException)->shouldBeCalled();
        $this->eventConverter->toDomainEvent(Argument::type(stdClass::class))->shouldNotBeCalled();

        $loader = $this->newLoader();
        $loader->query($this->builder->reveal(), $this->streamName)->current();
    }

    /**
     * @test
     */
    public function it_raise_exception_when_query_does_not_affect_rows(): void
    {
        $this->expectException(QueryFailure::class);

        // double SomeQueryException will set 0 code to string '00000'
        $queryException = new SomeQueryException(new RuntimeException('some message', 0));

        $this->builder->cursor()->willThrow($queryException)->shouldBeCalled();
        $this->eventConverter->toDomainEvent(Argument::type(stdClass::class))->shouldNotBeCalled();

        $loader = $this->newLoader();
        $loader->query($this->builder->reveal(), $this->streamName)->current();
    }

    /**
     * @test
     */
    public function it_raise_exception_when_stream_events_is_empty(): void
    {
        $this->expectException(StreamNotFound::class);
        $this->expectExceptionMessage('Stream customer not found');

        $this->builder->cursor()->willReturn(new LazyCollection())->shouldBeCalled();
        $this->eventConverter->toDomainEvent(Argument::type(stdClass::class))->shouldNotBeCalled();

        $loader = $this->newLoader();
        $loader->query($this->builder->reveal(), $this->streamName)->current();
    }

    private function newLoader(): StreamEventLoader
    {
        $eventConverter = $this->eventConverter->reveal();

        return new class($eventConverter) extends StreamEventLoader {

            public function __construct(protected EventConverter $eventConverter)
            {
                //
            }

            protected function generateFrom(Builder $builder, StreamName $StreamName): Collection|LazyCollection
            {
                return $builder->cursor();
            }
        };
    }
}
