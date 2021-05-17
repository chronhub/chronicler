<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit;

use Chronhub\Chronicler\Exception\ConcurrencyException;
use Chronhub\Chronicler\Exception\StreamAlreadyExists;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\GenericEventChronicler;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Support\Contracts\EventableChronicler;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Chronicler\Support\Contracts\Tracking\ContextualStream;
use Chronhub\Chronicler\Support\Contracts\Tracking\StreamTracker;
use Chronhub\Chronicler\Tests\Double\SomeDomainEvent;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Chronhub\Chronicler\Tracking\TrackStream;
use Chronhub\Foundation\Aggregate\GenericAggregateId;
use Chronhub\Foundation\Message\Message;
use Chronhub\Foundation\Support\Contracts\Tracker\Listener;
use Generator;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Throwable;
use function get_class;

/** @coversDefaultClass \Chronhub\Chronicler\ProvideEventsChronicle */
/** @coversDefaultClass \Chronhub\Chronicler\GenericEventChronicler */
final class GenericEventChroniclerTest extends TestCaseWithProphecy
{
    private Stream $stream;
    private Chronicler|ObjectProphecy $chronicler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stream = new Stream(new StreamName('customer'), []);
        $this->chronicler = $this->prophesize(Chronicler::class);
    }

    /**
     * @test
     */
    public function it_dispatch_first_commit_event(): void
    {
        $this->chronicler->persistFirstCommit($this->stream)->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FIRST_COMMIT_EVENT,
            function (ContextualStream $context): void {
                $this->assertEquals($this->stream, $context->stream());
            }
        );

        $eventChronicler->persistFirstCommit($this->stream);
    }

    /**
     * @test
     */
    public function it_raise_stream_already_exits_on_persist_first_commit(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $exception = StreamAlreadyExists::withStreamName(new StreamName('customer'));

        $this->chronicler->persistFirstCommit($this->stream)->willThrow($exception)->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FIRST_COMMIT_EVENT,
            function (ContextualStream $context) use ($exception): void {
                $this->assertEquals($exception, $context->exception());
            }
        );

        $eventChronicler->persistFirstCommit($this->stream);
    }

    /**
     * @test
     */
    public function it_dispatch_persist_stream_event(): void
    {
        $this->chronicler->persist($this->stream)->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::PERSIST_STREAM_EVENT,
            function (ContextualStream $context): void {
                $this->assertEquals($this->stream, $context->stream());
            }
        );

        $eventChronicler->persist($this->stream);
    }

    /**
     * @test
     * @dataProvider provideExceptionOnPersistStreamEvents
     * @param Throwable $exception
     */
    public function it_raise_exception_on_persist_stream_events(Throwable $exception): void
    {
        $this->expectException(get_class($exception));

        $this->chronicler->persist($this->stream)->willThrow($exception)->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::PERSIST_STREAM_EVENT,
            function (ContextualStream $context) use ($exception): void {
                $this->assertEquals($exception, $context->exception());
            }
        );

        $eventChronicler->persist($this->stream);
    }

    /**
     * @test
     */
    public function it_dispatch_delete_stream_event(): void
    {
        $this->chronicler->delete($this->stream->name())->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::PERSIST_STREAM_EVENT,
            function (ContextualStream $context): void {
                $this->assertEquals($this->stream->name(), $context->streamName());
            }
        );

        $eventChronicler->delete($this->stream->name());
    }

    /**
     * ^@test
     */
    public function it_raise_stream_not_found_exception_on_delete_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $exception = StreamNotFound::withStreamName($this->stream->name());

        $this->chronicler->delete($this->stream->name())->willThrow($exception)->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::DELETE_STREAM_EVENT,
            function (ContextualStream $context) use ($exception): void {
                $this->assertEquals($exception, $context->exception());
            }
        );

        $eventChronicler->delete($this->stream->name());
    }

    /**
     * @test
     * @dataProvider provideEventWithDirection
     * @param string $event
     * @param string $direction
     */
    public function it_dispatch_retrieve_all_events_with_direction(string $event, string $direction): void
    {
        $aggregateId = GenericAggregateId::create();
        $expectedEvents = [
            new Message(SomeDomainEvent::fromContent(['foo'])),
            new Message(SomeDomainEvent::fromContent(['bar']))
        ];

        $this->chronicler
            ->retrieveAll($this->stream->name(), $aggregateId, $direction)
            ->willYield($expectedEvents)
            ->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            $event,
            function (ContextualStream $context) use ($event, $direction, $aggregateId): void {
                $this->assertEquals($event, $context->currentEvent());
                $this->assertEquals($direction, $context->direction());
                $this->assertEquals($aggregateId, $context->aggregateId());
            }
        );

        $messages = $eventChronicler->retrieveAll($this->stream->name(), $aggregateId, $direction);

        $messages = iterator_to_array($messages);

        $this->assertEquals($expectedEvents, $messages);
    }

    /**
     * @test
     * @dataProvider provideEventWithDirection
     * @param string $event
     * @param string $direction
     */
    public function it_raise_stream_not_found_exception_on_retrieve_all_events(string $event, string $direction): void
    {
        $this->expectException(StreamNotFound::class);

        $aggregateId = GenericAggregateId::create();
        $exception = StreamNotFound::withStreamName($this->stream->name());

        $this->chronicler
            ->retrieveAll($this->stream->name(), $aggregateId, $direction)
            ->willThrow($exception)
            ->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            $event,
            function (ContextualStream $context) use ($event, $direction, $aggregateId, $exception): void {
                $this->assertEquals($event, $context->currentEvent());
                $this->assertEquals($direction, $context->direction());
                $this->assertEquals($aggregateId, $context->aggregateId());
                $this->assertEquals($exception, $context->exception());
            }
        );

        $eventChronicler->retrieveAll($this->stream->name(), $aggregateId, $direction)->current();
    }

    /**
     * @test
     */
    public function it_dispatch_retrieve_events_with_query_filter(): void
    {
        $queryFilter = $this->prophesize(QueryFilter::class)->reveal();

        $expectedEvents = [
            new Message(SomeDomainEvent::fromContent(['name' => 'steph'])),
            new Message(SomeDomainEvent::fromContent(['name' => 'bug']))
        ];

        $this->chronicler
            ->retrieveFiltered($this->stream->name(), $queryFilter)
            ->willYield($expectedEvents)
            ->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FILTERED_STREAM_EVENT,
            function (ContextualStream $context) use ($queryFilter): void {
                $this->assertEquals($this->stream->name(), $context->streamName());
                $this->assertEquals($queryFilter, $context->queryFilter());
            }
        );

        $messages = $eventChronicler->retrieveFiltered($this->stream->name(), $queryFilter);

        $messages = iterator_to_array($messages);

        $this->assertEquals($expectedEvents, $messages);
    }

    /**
     * @test
     */
    public function it_raise_stream_not_found_exception_on_retrieve_events_with_query_filter(): void
    {
        $this->expectException(StreamNotFound::class);

        $exception = StreamNotFound::withStreamName($this->stream->name());

        $queryFilter = $this->prophesize(QueryFilter::class)->reveal();

        $this->chronicler
            ->retrieveFiltered($this->stream->name(), $queryFilter)
            ->willThrow($exception)
            ->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FILTERED_STREAM_EVENT,
            function (ContextualStream $context) use ($queryFilter, $exception): void {
                $this->assertEquals($this->stream->name(), $context->streamName());
                $this->assertEquals($queryFilter, $context->queryFilter());
                $this->assertEquals($exception, $context->exception());
            }
        );

        $eventChronicler->retrieveFiltered($this->stream->name(), $queryFilter)->current();
    }

    /**
     * @test
     * @dataProvider provideBool
     * @param bool $streamExists
     */
    public function it_dispatch_has_stream_event(bool $streamExists): void
    {
        $this->chronicler->hasStream($this->stream->name())->willReturn($streamExists)->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::HAS_STREAM_EVENT,
            function (ContextualStream $context) use ($streamExists): void {
                $this->assertEquals($streamExists, $context->isStreamExists());
            }
        );

        $eventChronicler->hasStream($this->stream->name());
    }

    /**
     * @test
     */
    public function it_dispatch_fetch_stream_names_and_return_stream_names_if_exists(): void
    {
        $fooStreamName = new StreamName('customer');
        $barStreamName = new StreamName('account');

        $this->chronicler->fetchStreamNames($fooStreamName, $barStreamName)
            ->willReturn([$fooStreamName])->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FETCH_STREAM_NAMES,
            function (ContextualStream $context) use ($fooStreamName): void {
                $this->assertEquals([$fooStreamName], $context->streamNames());
            }
        );

        $this->assertEquals([$fooStreamName], $eventChronicler->fetchStreamNames($fooStreamName, $barStreamName));
    }

    /**
     * @test
     */
    public function it_dispatch_fetch_category_names_and_return_category_names_if_exists(): void
    {
        $categories = ['customer-123', 'customer-124'];

        $this->chronicler->fetchCategoryNames('customer-123', 'customer-124')
            ->willReturn($categories)
            ->shouldBeCalled();

        $eventChronicler = $this->eventChroniclerInstance(
            EventableChronicler::FETCH_CATEGORY_NAMES,
            function (ContextualStream $context) use ($categories): void {
                $this->assertEquals($categories, $context->categoryNames());
            }
        );

        $this->assertEquals(
            $categories,
            $eventChronicler->fetchCategoryNames('customer-123', 'customer-124')
        );
    }

    /**
     * @test
     */
    public function it_unsubscribe_subscribers(): void
    {
        $tracker = $this->prophesize(StreamTracker::class);
        $tracker->listen(Argument::type('string'), Argument::type('callable'))
            ->willReturn($this->prophesize(Listener::class)->reveal());

        $tracker->forget(Argument::type(Listener::class))->shouldBeCalledTimes(3);

        $chronicler = new GenericEventChronicler($this->chronicler->reveal(), $tracker->reveal());

        $subscribers = [
            $this->prophesize(Listener::class)->reveal(),
            $this->prophesize(Listener::class)->reveal(),
            $this->prophesize(Listener::class)->reveal(),
        ];

        $chronicler->unsubscribe(...$subscribers);
    }

    /**
     * @test
     */
    public function it_access_inner_chronicler(): void
    {
        $tracker = new TrackStream();
        $eventChronicler = new GenericEventChronicler($this->chronicler->reveal(), $tracker);

        $this->assertEquals($this->chronicler->reveal(), $eventChronicler->innerChronicler());
    }

    public function provideEventWithDirection(): Generator
    {
        yield [EventableChronicler::ALL_STREAM_EVENT, 'asc'];

        yield [EventableChronicler::ALL_REVERSED_STREAM_EVENT, 'desc'];
    }

    public function provideExceptionOnPersistStreamEvents(): Generator
    {
        yield [StreamNotFound::withStreamName(new StreamName('foo'))];

        yield [new ConcurrencyException('some exception message')];
    }

    public function provideBool(): Generator
    {
        yield [true];

        yield [false];
    }

    private function eventChroniclerInstance(string $event, callable $assert, int $priority = 0): GenericEventChronicler
    {
        $tracker = new TrackStream();

        $eventChronicler = new GenericEventChronicler($this->chronicler->reveal(), $tracker);

        $eventChronicler->subscribe($event, $assert, $priority);

        return $eventChronicler;
    }
}
