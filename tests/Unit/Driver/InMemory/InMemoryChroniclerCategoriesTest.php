<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Driver\InMemory;

use Generator;
use Illuminate\Support\Str;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Tests\TestCase;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\Tests\Double\SomeDomainEvent;
use Chronhub\Foundation\Aggregate\GenericAggregateId;
use Chronhub\Chronicler\Exception\StreamAlreadyExists;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Chronicler\Driver\InMemory\InMemoryChronicler;
use Chronhub\Chronicler\Driver\InMemory\InMemoryEventStream;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\Contracts\Query\InMemoryQueryFilter;

final class InMemoryChroniclerCategoriesTest extends TestCase
{
    private InMemoryChronicler $chronicler;
    private AggregateId $aggregateId;
    private StreamName $streamName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chronicler = new InMemoryChronicler(new InMemoryEventStream());
        $this->aggregateId = GenericAggregateId::create();

        $this->streamName = new StreamName('customer-' . $this->aggregateId->toString());
    }

    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $this->assertFalse($this->chronicler->hasStream($this->streamName));
        $this->assertEmpty($this->chronicler->streams());
    }

    /**
     * @test
     */
    public function it_persist_first_commit(): void
    {
        $stream = new Stream($this->streamName);

        $this->chronicler->persistFirstCommit($stream);

        $this->assertTrue($this->chronicler->hasStream($this->streamName));
    }

    /**
     * @test
     */
    public function it_raises_exception_when_stream_name_already_exists(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $stream = new Stream($this->streamName);

        $this->chronicler->persistFirstCommit($stream);
        $this->chronicler->persistFirstCommit($stream);
    }

    /**
     * @test
     */
    public function it_persist_events_on_first_commit(): void
    {
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 10));

        $stream = new Stream($this->streamName, $events);

        $this->chronicler->persistFirstCommit($stream);

        $this->assertEquals(
            [$this->streamName->toString() => $events],
            $this->chronicler->streams()->toArray()
        );
    }

    /**
     * @test
     */
    public function it_persist_events_on_first_commit_with_one_stream_strategy(): void
    {
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 10));

        $stream = new Stream($this->streamName, $events);

        $this->chronicler->persistFirstCommit($stream);

        $this->assertEquals(
            [$this->streamName->toString() => $events],
            $this->chronicler->streams()->toArray()
        );
    }

    /**
     * @test
     */
    public function it_decorate_internal_position_header_with_aggregate_version(): void
    {
        $headers = [
            Header::AGGREGATE_VERSION => 12,
            Header::AGGREGATE_ID      => $this->aggregateId->toString(),
        ];

        $event = SomeDomainEvent::fromContent(['password' => Str::random()])->withHeaders($headers);

        $stream = new Stream($this->streamName, [$event]);

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

        $stream = new Stream($this->streamName, []);

        $this->chronicler->persist($stream);
    }

    /**
     * @test
     */
    public function it_delete_stream(): void
    {
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 10));

        $stream = new Stream($this->streamName, []);

        $this->assertFalse($this->chronicler->hasStream($this->streamName));

        $this->chronicler->persistFirstCommit($stream);

        $this->assertTrue($this->chronicler->hasStream($this->streamName));
        $this->assertEquals([$this->streamName->toString() => []], $this->chronicler->streams()->toArray());

        $this->chronicler->persist(new Stream($this->streamName, $events));
        $this->assertEquals([$this->streamName->toString() => $events], $this->chronicler->streams()->toArray());

        $this->chronicler->delete($this->streamName);

        $this->assertFalse($this->chronicler->hasStream($this->streamName));
        $this->assertEmpty($this->chronicler->streams());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_deleting_stream_not_found(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->chronicler->delete($this->streamName);
    }

    /**
     * @test
     * @dataProvider provideDirection
     */
    public function it_retrieve_all_stream_with_direction(string $direction): void
    {
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 5));

        $stream = new Stream($this->streamName, $events);

        $this->chronicler->persistFirstCommit($stream);

        $recordedEvents = $this->chronicler->retrieveAll($this->streamName, $this->aggregateId, $direction);

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
        $headers = [
            Header::INTERNAL_POSITION => $currentVersion = 5,
            Header::AGGREGATE_VERSION => $currentVersion,
            Header::AGGREGATE_ID      => $this->aggregateId,
        ];

        $event = SomeDomainEvent::fromContent(['password' => Str::random()])->withHeaders($headers);

        $stream = new Stream($this->streamName, [$event]);

        $this->chronicler->persistFirstCommit($stream);

        $recordedEvents = $this->chronicler->retrieveAll($this->streamName, $this->aggregateId);
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
        $events = iterator_to_array($this->providePastEvent($this->aggregateId, 10));

        $stream = new Stream($this->streamName, $events);

        $this->chronicler->persistFirstCommit($stream);

        $recordedEvents = $this->chronicler->retrieveFiltered($this->streamName, $filter);

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
    public function it_fetch_categories(): void
    {
        $firstStream = new StreamName('customer-123');
        $secondStream = new StreamName('customer-456');

        $this->assertFalse($this->chronicler->hasStream($firstStream));
        $this->assertFalse($this->chronicler->hasStream($secondStream));

        $this->chronicler->persistFirstCommit(new Stream($firstStream));
        $this->chronicler->persistFirstCommit(new Stream($secondStream));

        $this->assertTrue($this->chronicler->hasStream($firstStream));
        $this->assertTrue($this->chronicler->hasStream($secondStream));

        $wanted = ['customer-456'];
        $this->assertEquals([], $this->chronicler->fetchCategoryNames(...$wanted));

        $wanted = ['customer-123', 'customer-456'];
        $this->assertEquals([], $this->chronicler->fetchCategoryNames(...$wanted));

        $wanted = ['customer'];
        $this->assertEquals(['customer-123', 'customer-456'], $this->chronicler->fetchCategoryNames(...$wanted));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_retrieving_stream_not_found(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->chronicler->retrieveAll($this->streamName, $this->aggregateId)->current();
    }

    /**
     * @test
     */
    public function it_raise_exception_when_retrieving_stream_exists_and_no_events(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->chronicler->persistFirstCommit(new Stream($this->streamName));

        $this->chronicler->retrieveAll($this->streamName, $this->aggregateId)->current();
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
            }, range(5, 10),
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
            }, range(3, 5),
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
            }, array_reverse(range(1, 4)),
        ];
    }

    private function providePastEvent(AggregateId $aggregateId, int $limit = 5): Generator
    {
        $version = 0;

        while (0 !== $limit) {
            $headers = [
                Header::INTERNAL_POSITION => $currentVersion = ++$version,
                Header::AGGREGATE_VERSION => $currentVersion,
                Header::AGGREGATE_ID      => $aggregateId->toString(),
            ];

            yield SomeDomainEvent::fromContent(['password' => Str::random()])->withHeaders($headers);

            --$limit;
        }
    }
}
