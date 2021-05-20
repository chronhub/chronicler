<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Aggregate;

use Generator;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Tests\Double\SomeDomainEvent;
use Chronhub\Foundation\Aggregate\GenericAggregateId;
use Chronhub\Chronicler\Tests\Double\SomeAggregateRoot;
use Chronhub\Chronicler\Aggregate\AggregateEventReleaser;
use Chronhub\Chronicler\Support\Contracts\StreamProducer;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Chronicler\Aggregate\GenericAggregateRepository;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateType;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateCache;

/** @coversDefaultClass \Chronhub\Chronicler\Aggregate\GenericAggregateRepository */
final class GenericAggregateRepositoryTest extends TestCaseWithProphecy
{
    private ObjectProphecy|AggregateType $aggregateType;
    private ObjectProphecy|AggregateCache $aggregateCache;
    private ObjectProphecy|Chronicler $chronicler;
    private ObjectProphecy|StreamProducer $streamProducer;
    private ObjectProphecy|AggregateEventReleaser $eventReleaser;
    private ObjectProphecy|AggregateRoot $aggregateRoot;

    protected function setUp(): void
    {
        $this->aggregateType = $this->prophesize(AggregateType::class);
        $this->aggregateCache = $this->prophesize(AggregateCache::class);
        $this->chronicler = $this->prophesize(Chronicler::class);
        $this->streamProducer = $this->prophesize(StreamProducer::class);
        $this->eventReleaser = $this->prophesize(AggregateEventReleaser::class);
        $this->aggregateRoot = $this->prophesize(AggregateRoot::class);
    }

    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $repository = $this->repositoryInstance();

        $this->assertEquals($this->chronicler->reveal(), $repository->chronicler());
    }

    /**
     * @test
     */
    public function it_create_stream_if_event_is_first_version_and_strategy_is_one_stream_per_aggregate(): void
    {
        $aggregateId = GenericAggregateId::create();
        $streamName = new StreamName('customer');

        $this->aggregateType->assertAggregateRootIsSupported($this->aggregateRoot->reveal()::class);
        $this->aggregateRoot->aggregateId()->willReturn($aggregateId)->shouldBeCalled();
        $this->aggregateCache->forget($aggregateId)->shouldBeCalled();

        $releasedEvents = [
            SomeDomainEvent::fromContent(['steph' => 'bug'])
                ->withHeaders([
                    Header::AGGREGATE_VERSION => 1,
                ]),
        ];

        $this->eventReleaser
            ->releaseEvents($this->aggregateRoot->reveal())
            ->willReturn($releasedEvents)
            ->shouldBeCalled();

        $stream = new Stream($streamName, $releasedEvents);

        $this->streamProducer
            ->produceStream($aggregateId, $releasedEvents)
            ->willReturn($stream)
            ->shouldBeCalled();

        $this->streamProducer
            ->isFirstCommit($releasedEvents[0])
            ->willReturn(true)
            ->shouldBeCalled();

        $this->chronicler->persistFirstCommit(new Stream($streamName, $releasedEvents));

        $repository = $this->repositoryInstance();

        $repository->persist($this->aggregateRoot->reveal());
    }

    /**
     * @test
     *
     * aka it never create create stream with single strategy even with first version
     */
    public function it_always_persist_with_single_stream_per_strategy_and_first_event_version(): void
    {
        $aggregateId = GenericAggregateId::create();
        $streamName = new StreamName('customer');

        $this->aggregateType->assertAggregateRootIsSupported($this->aggregateRoot->reveal()::class);
        $this->aggregateRoot->aggregateId()->willReturn($aggregateId)->shouldBeCalled();
        $this->aggregateCache->forget($aggregateId)->shouldBeCalled();

        $releasedEvents = [
            SomeDomainEvent::fromContent(['steph' => 'bug'])
                ->withHeaders([
                    Header::AGGREGATE_VERSION => 1,
                ]),
        ];

        $this->eventReleaser
            ->releaseEvents($this->aggregateRoot->reveal())
            ->willReturn($releasedEvents)
            ->shouldBeCalled();

        $stream = new Stream($streamName, $releasedEvents);

        $this->streamProducer
            ->produceStream($aggregateId, $releasedEvents)
            ->willReturn($stream)
            ->shouldBeCalled();

        $this->streamProducer
            ->isFirstCommit($releasedEvents[0])
            ->willReturn(false)
            ->shouldBeCalled();

        $this->chronicler->persist(new Stream($streamName, $releasedEvents));

        $this->repositoryInstance()->persist($this->aggregateRoot->reveal());
    }

    /**
     * @test
     */
    public function it_always_persist_when_event_version_is_not_first_and_no_matter_strategy(): void
    {
        $aggregateId = GenericAggregateId::create();
        $streamName = new StreamName('customer');

        $this->aggregateType->assertAggregateRootIsSupported($this->aggregateRoot->reveal()::class);
        $this->aggregateRoot->aggregateId()->willReturn($aggregateId)->shouldBeCalled();
        $this->aggregateCache->forget($aggregateId)->shouldBeCalled();

        $releasedEvents = [
            SomeDomainEvent::fromContent(['steph' => 'bug'])
                ->withHeaders([
                    Header::AGGREGATE_VERSION => 10,
                ]),
        ];

        $this->eventReleaser
            ->releaseEvents($this->aggregateRoot->reveal())
            ->willReturn($releasedEvents)
            ->shouldBeCalled();

        $this->streamProducer->isOneStreamPerAggregate()->shouldNotBeCalled();

        $stream = new Stream($streamName, $releasedEvents);

        $this->streamProducer
            ->produceStream($aggregateId, $releasedEvents)
            ->willReturn($stream)
            ->shouldBeCalled();

        $this->streamProducer
            ->isFirstCommit($releasedEvents[0])
            ->willReturn(false)
            ->shouldBeCalled();

        $this->chronicler->persist(new Stream($streamName, $releasedEvents));

        $this->repositoryInstance()->persist($this->aggregateRoot->reveal());
    }

    /**
     * @test
     */
    public function it_does_not_persist_if_released_events_is_empty(): void
    {
        $this->aggregateType->assertAggregateRootIsSupported($this->aggregateRoot->reveal()::class);

        $this->aggregateRoot->aggregateId()->shouldNotBeCalled();
        $this->aggregateCache->forget(Argument::type(AggregateId::class))->shouldNotBeCalled();
        $this->streamProducer->isOneStreamPerAggregate()->shouldNotBeCalled();
        $this->streamProducer->determineStreamName(Argument::type('string'))->shouldNotBeCalled();

        $this->eventReleaser->releaseEvents($this->aggregateRoot->reveal())->willReturn([])->shouldBeCalled();

        $this->chronicler->persist(Argument::type(Stream::class))->shouldNotBeCalled();

        $this->repositoryInstance()->persist($this->aggregateRoot->reveal());
    }

    /**
     * @test
     */
    public function it_retrieve_aggregate_from_cache(): void
    {
        $aggregateId = GenericAggregateId::create();
        $streamName = new StreamName('customer');

        $this->aggregateCache->has($aggregateId)->willReturn(true)->shouldBeCalled();
        $this->aggregateCache->get($aggregateId)->willReturn($this->aggregateRoot->reveal())->shouldBeCalled();
        $this->streamProducer->determineStreamName($aggregateId->toString())->shouldNotBeCalled();
        $this->chronicler->retrieveAll($streamName, $aggregateId)->shouldNotBeCalled();
        $this->aggregateType->determineFromEvent(Argument::type(DomainEvent::class))->shouldNotBeCalled();

        $repository = $this->repositoryInstance();

        $aggregateRoot = $repository->retrieve($aggregateId);

        $this->assertEquals($this->aggregateRoot->reveal(), $aggregateRoot);
    }

    /**
     * @test
     */
    public function it_return_aggregate_reconstitute_from_events(): void
    {
        $aggregateId = GenericAggregateId::create();
        $streamName = new StreamName('customer');

        $this->aggregateCache->has($aggregateId)->willReturn(false)->shouldBeCalled();
        $this->aggregateCache->get($aggregateId)->shouldNotBeCalled();
        $this->aggregateCache->put(Argument::type(SomeAggregateRoot::class))->shouldBeCalled();
        $this->streamProducer->determineStreamName($aggregateId->toString())->willReturn($streamName)->shouldBeCalled();

        $firstEvent = SomeDomainEvent::fromContent(['name' => 'bug']);

        $this->chronicler
            ->retrieveAll($streamName, $aggregateId)
            ->willReturn($this->provideEvents($firstEvent))
            ->shouldBeCalled();

        $this->aggregateType
            ->determineFromEvent($firstEvent)
            ->willReturn(SomeAggregateRoot::class)
            ->shouldBeCalled();

        $repository = $this->repositoryInstance();

        $aggregateRoot = $repository->retrieve($aggregateId);

        $this->assertInstanceOf(SomeAggregateRoot::class, $aggregateRoot);
        $this->assertEquals(2, $aggregateRoot->version());
    }

    /**
     * @test
     */
    public function it_flush_aggregate_cache(): void
    {
        $this->aggregateCache->flush()->willReturn(true)->shouldBeCalled();

        $repository = $this->repositoryInstance();

        $repository->flushCache();
    }

    private function provideEvents(DomainEvent $firstEvent): Generator
    {
        yield from [
            $firstEvent,
            SomeDomainEvent::fromContent(['name' => 'steph']),
        ];

        return 2;
    }

    private function repositoryInstance(): GenericAggregateRepository
    {
        return new GenericAggregateRepository(
            $this->aggregateType->reveal(),
            $this->chronicler->reveal(),
            $this->streamProducer->reveal(),
            $this->aggregateCache->reveal(),
            $this->eventReleaser->reveal(),
        );
    }
}
