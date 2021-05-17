<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Producer;

use Chronhub\Chronicler\Producer\OneStreamPerAggregate;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Tests\Double\SomeDomainEvent;
use Chronhub\Chronicler\Tests\TestCase;
use Chronhub\Foundation\Aggregate\GenericAggregateId;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Generator;

/** @coversDefaultClass \Chronhub\Chronicler\Producer\OneStreamPerAggregate */
final class OneStreamPerAggregateTest extends TestCase
{
    /**
     * @test
     */
    public function it_determine_stream_name(): void
    {
        $streamName = new StreamName('customer');

        $streamProducer = new OneStreamPerAggregate($streamName);

        $aggregateId = GenericAggregateId::create()->toString();

        $this->assertEquals(
            new StreamName('customer-' . $aggregateId),
            $streamProducer->determineStreamName($aggregateId)
        );
    }

    /**
     * @test
     * @dataProvider provideEvents
     */
    public function it_produce_stream(iterable $events): void
    {
        $streamName = new StreamName('customer');
        $aggregateId = GenericAggregateId::create();

        $streamProducer = new OneStreamPerAggregate($streamName);

        $this->assertEquals(
            new Stream(new StreamName('customer-' . $aggregateId->toString()), $events),
            $streamProducer->produceStream($aggregateId, $events)
        );
    }

    /**
     * @test
     * @dataProvider provideEventsForFirstCommit
     */
    public function it_determine_if_event_is_first_commit(DomainEvent $event, bool $isFirstCommit): void
    {
        $streamName = new StreamName('customer');

        $streamProducer = new OneStreamPerAggregate($streamName);

        $this->assertEquals($isFirstCommit, $streamProducer->isFirstCommit($event));
    }

    /**
     * @test
     */
    public function it_check_if_is_one_stream_per_aggregate_strategy(): void
    {
        $streamName = new StreamName('customer');

        $streamProducer = new OneStreamPerAggregate($streamName);

        $this->assertTrue($streamProducer->isOneStreamPerAggregate());
    }

    public function provideEvents(): Generator
    {
        yield [[]];
        yield [[SomeDomainEvent::fromContent(['steph' => 'bug'])]];
    }

    public function provideEventsForFirstCommit(): Generator
    {
        yield [
            SomeDomainEvent::fromContent(['steph' => 'bug'])
                ->withHeader(Header::AGGREGATE_VERSION, 1),
            true
        ];

        yield [
            SomeDomainEvent::fromContent(['steph' => 'bug'])
                ->withHeader(Header::AGGREGATE_VERSION, 2),
            false
        ];

        yield [
            SomeDomainEvent::fromContent(['steph' => 'bug'])
                ->withHeader(Header::AGGREGATE_VERSION, 20),
            false
        ];
    }
}
