<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Driver\InMemory;

use Chronhub\Chronicler\Driver\InMemory\InMemoryChronicler;
use Chronhub\Chronicler\Driver\InMemory\InMemoryEventStream;
use Chronhub\Chronicler\Exception\StreamAlreadyExists;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Contracts\Query\InMemoryQueryFilter;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Chronicler\Tests\Double\SomeDomainEvent;
use Chronhub\Chronicler\Tests\TestCase;
use Chronhub\Foundation\Aggregate\GenericAggregateId;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Generator;
use Illuminate\Support\Str;
use function array_reverse;
use function iterator_to_array;

/** @coversDefaultClass \Chronhub\Chronicler\Driver\InMemory\InMemoryChronicler */
final class InMemoryChroniclerTest extends TestCase
{
    private InMemoryChronicler $chronicler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chronicler = new InMemoryChronicler(new InMemoryEventStream());
    }

    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $streamName = new StreamName('customer_stream');
        $this->assertFalse($this->chronicler->hasStream($streamName));
        $this->assertEmpty($this->chronicler->streams());
    }

    /**
     * @test
     */
    public function it_persist_first_commit(): void
    {
        $streamName = new StreamName('customer_stream');
        $stream = new Stream($streamName);

        $this->chronicler->persistFirstCommit($stream);

        $this->assertTrue($this->chronicler->hasStream($streamName));
    }

    /**
     * @test
     */
    public function it_raises_exception_when_stream_name_already_exists(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $streamName = new StreamName('customer_stream');
        $stream = new Stream($streamName);

        $this->chronicler->persistFirstCommit($stream);
        $this->chronicler->persistFirstCommit($stream);
    }

    /**
     * @test
     */
    public function it_persist_events_on_first_commit(): void
    {
        $streamName = new StreamName('customer_stream');
        $aggregateId = GenericAggregateId::create();
        $events = iterator_to_array($this->providePastEvent($aggregateId, 10));

        $stream = new Stream($streamName, $events);

        $this->chronicler->persistFirstCommit($stream);

        $this->assertEquals(['customer_stream' => $events], $this->chronicler->streams()->toArray());
    }

    /**
     * @test
     */
    public function it_persist_events_on_first_commit_with_one_stream_strategy(): void
    {
        $streamName = new StreamName('customer_stream');
        $aggregateId = GenericAggregateId::create();
        $events = iterator_to_array($this->providePastEvent($aggregateId, 10));

        $stream = new Stream($streamName, $events);

        $this->chronicler->persistFirstCommit($stream);

        $this->assertEquals(['customer_stream' => $events], $this->chronicler->streams()->toArray());
    }

    /**
     * @test
     */
    public function it_decorate_internal_position_header_with_aggregate_version(): void
    {
        $streamName = new StreamName('customer_stream');
        $aggregateId = GenericAggregateId::create();

        $headers = [
            Header::AGGREGATE_VERSION => 12,
            Header::AGGREGATE_ID      => $aggregateId->toString()
        ];

        $event = SomeDomainEvent::fromContent(['password' => Str::random()])->withHeaders($headers);

        $stream = new Stream($streamName, [$event]);

        $this->chronicler->persistFirstCommit($stream);

        $pastEvent = $this->chronicler->streams()->first()[0];

        $this->assertArrayHasKey(Header::INTERNAL_POSITION, $pastEvent->headers());
        $this->assertEquals(12, $pastEvent->header(Header::INTERNAL_POSITION));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_persist_event_with_not_found_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = new StreamName('customer_stream');

        $stream = new Stream($streamName, []);

        $this->chronicler->persist($stream);
    }

    /**
     * @test
     */
    public function it_delete_stream(): void
    {
        $streamName = new StreamName('customer_stream');
        $aggregateId = GenericAggregateId::create();
        $events = iterator_to_array($this->providePastEvent($aggregateId, 10));

        $stream = new Stream($streamName, []);

        $this->assertFalse($this->chronicler->hasStream($streamName));

        $this->chronicler->persistFirstCommit($stream);

        $this->assertTrue($this->chronicler->hasStream($streamName));
        $this->assertEquals(['customer_stream' => []], $this->chronicler->streams()->toArray());

        $this->chronicler->persist(new Stream($streamName, $events));
        $this->assertEquals(['customer_stream' => $events], $this->chronicler->streams()->toArray());

        $this->chronicler->delete($streamName);

        $this->assertFalse($this->chronicler->hasStream($streamName));
        $this->assertEmpty($this->chronicler->streams());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_deleting_stream_not_found()
    {
        $this->expectException(StreamNotFound::class);

        $streamName = new StreamName('customer_stream');

        $this->chronicler->delete($streamName);
    }

    /**
     * @test
     * @dataProvider provideDirection
     * @param string $direction
     */
    public function it_retrieve_all_stream_with_direction(string $direction): void
    {
        $streamName = new StreamName('customer_stream');
        $aggregateId = GenericAggregateId::create();
        $events = iterator_to_array($this->providePastEvent($aggregateId, 5));

        $stream = new Stream($streamName, $events);

        $this->chronicler->persistFirstCommit($stream);

        $recordedEvents = $this->chronicler->retrieveAll($streamName, $aggregateId, $direction);

        $allEvents = [];
        foreach ($recordedEvents as $recordedEvent) {
            $allEvents[] = $recordedEvent;
        }

        $this->assertEquals(5, $recordedEvents->getReturn());
        $this->assertCount(5, $allEvents);

        $range = range(1, 5);

        if ('desc' === $direction) {
            $range = array_reverse($range);
        }

        $this->assertEquals($range, array_map(function (DomainEvent $event): int {
            return $event->header(Header::INTERNAL_POSITION);
        }, $allEvents));
    }

    /**
     * @test
     */
    public function it_retrieve_all_with_aggregate_id_instance_in_header(): void
    {
        $streamName = new StreamName('customer_stream');
        $aggregateId = GenericAggregateId::create();

        $headers = [
            Header::INTERNAL_POSITION => $currentVersion = 5,
            Header::AGGREGATE_VERSION => $currentVersion,
            Header::AGGREGATE_ID      => $aggregateId
        ];

        $event = SomeDomainEvent::fromContent(['password' => Str::random()])->withHeaders($headers);

        $stream = new Stream($streamName, [$event]);

        $this->chronicler->persistFirstCommit($stream);

        $recordedEvents = $this->chronicler->retrieveAll($streamName, $aggregateId);
        $recordedEvent = $recordedEvents->current();
        $recordedEvents->next();

        $this->assertEquals(1, $recordedEvents->getReturn());
        $this->assertEquals($event, $recordedEvent);
    }

    /**
     * @test
     * @dataProvider provideFilter
     */
    public function it_filter_stream_events(QueryFilter $filter, array $range): void
    {
        $streamName = new StreamName('customer_stream');
        $aggregateId = GenericAggregateId::create();
        $events = iterator_to_array($this->providePastEvent($aggregateId, 10));

        $stream = new Stream($streamName, $events);

        $this->chronicler->persistFirstCommit($stream);

        $recordedEvents = $this->chronicler->retrieveFiltered($streamName, $filter);

        $allEvents = [];
        foreach ($recordedEvents as $recordedEvent) {
            $allEvents[] = $recordedEvent;
        }

        $this->assertEquals(count($range), $recordedEvents->getReturn());
        $this->assertCount(count($range), $allEvents);

        $this->assertEquals($range, array_map(function (DomainEvent $event): int {
            return $event->header(Header::INTERNAL_POSITION);
        }, $allEvents));
    }

    /**
     * @test
     */
    public function it_fetch_stream_names(): void
    {
        $customerStream = new StreamName('customer_stream');
        $orderStream = new StreamName('order_stream');

        $this->assertFalse($this->chronicler->hasStream($customerStream));
        $this->assertFalse($this->chronicler->hasStream($orderStream));

        $this->chronicler->persistFirstCommit(new Stream($customerStream));
        $this->chronicler->persistFirstCommit(new Stream($orderStream));

        $this->assertTrue($this->chronicler->hasStream($customerStream));
        $this->assertTrue($this->chronicler->hasStream($orderStream));

        $wanted = [$customerStream, $orderStream];
        $this->assertEquals(['customer_stream', 'order_stream'], $this->chronicler->fetchStreamNames(...$wanted));

        $wanted = [$orderStream, new StreamName('does_not_exist')];
        $this->assertEquals(['order_stream'], $this->chronicler->fetchStreamNames(...$wanted));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_retrieving_stream_not_found(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = new StreamName('customer_stream');
        $aggregateId = GenericAggregateId::create();

        $this->chronicler->retrieveAll($streamName, $aggregateId)->current();
    }

    /**
     * @test
     */
    public function it_raise_exception_when_retrieving_stream_exists_and_no_events(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = new StreamName('customer_stream');
        $aggregateId = GenericAggregateId::create();

        $this->chronicler->persistFirstCommit(new Stream($streamName));

        $this->chronicler->retrieveAll($streamName, $aggregateId)->current();
    }

    /**
     * @test
     */
    public function it_return_empty_array_when_pulling_cache_streams(): void
    {
        $this->assertEquals([], $this->chronicler->pullCachedRecordedEvents());
    }

    public function provideDirection(): Generator
    {
        yield ['asc'];
        yield ['desc'];
    }

    public function provideFilter(): Generator
    {
        yield [
            new class() implements InMemoryQueryFilter {
                public function filterQuery(): callable
                {
                    return function (DomainEvent $event): bool {
                        return $event->header(Header::INTERNAL_POSITION) >= 5;
                    };
                }

                public function orderBy(): string
                {
                    return 'asc';
                }
            }, range(5, 10)
        ];

        yield [
            new class() implements InMemoryQueryFilter {
                public function filterQuery(): callable
                {
                    return function (DomainEvent $event): bool {
                        return $event->header(Header::INTERNAL_POSITION) >= 3
                            && $event->header(Header::INTERNAL_POSITION) <= 5;
                    };
                }

                public function orderBy(): string
                {
                    return 'asc';
                }
            }, range(3, 5)
        ];

        yield [
            new class() implements InMemoryQueryFilter {
                public function filterQuery(): callable
                {
                    return function (DomainEvent $event): bool {
                        return $event->header(Header::INTERNAL_POSITION) >= 1
                            && $event->header(Header::INTERNAL_POSITION) <= 4;
                    };
                }

                public function orderBy(): string
                {
                    return 'desc';
                }
            }, array_reverse(range(1, 4))
        ];
    }

    private function providePastEvent(AggregateId $aggregateId, int $limit = 5): Generator
    {
        $version = 0;

        while ($limit !== 0) {
            $headers = [
                Header::INTERNAL_POSITION => $currentVersion = ++$version,
                Header::AGGREGATE_VERSION => $currentVersion,
                Header::AGGREGATE_ID      => $aggregateId->toString()
            ];

            yield SomeDomainEvent::fromContent(['password' => Str::random()])->withHeaders($headers);

            $limit--;
        }
    }
}
