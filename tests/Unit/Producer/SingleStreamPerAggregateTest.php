<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Producer;

use Chronhub\Chronicler\Producer\SingleStreamPerAggregate;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Tests\Double\SomeDomainEvent;
use Chronhub\Chronicler\Tests\TestCase;
use Chronhub\Foundation\Aggregate\GenericAggregateId;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Generator;

/** @coversDefaultClass \Chronhub\Chronicler\Producer\SingleStreamPerAggregate */
final class SingleStreamPerAggregateTest extends TestCase
{
    /**
     * @test
     */
    public function it_determine_stream_name(): void
    {
        $streamName = new StreamName('customer');

        $streamProducer = new SingleStreamPerAggregate($streamName);

        $this->assertEquals(
            $streamName,
            $streamProducer->determineStreamName(GenericAggregateId::create()->toString())
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

        $streamProducer = new SingleStreamPerAggregate($streamName);

        $this->assertEquals(
            new Stream($streamName, $events),
            $streamProducer->produceStream($aggregateId, $events)
        );
    }

    /**
     * @test
     */
    public function it_always_return_false_to_determine_is_first_commit(): void
    {
        $streamName = new StreamName('customer');

        $streamProducer = new SingleStreamPerAggregate($streamName);

        /** @var DomainEvent $event */
        $event = SomeDomainEvent::fromContent(['steph' => 'bug'])
            ->withHeader(Header::AGGREGATE_VERSION, 1);

        $this->assertFalse($streamProducer->isFirstCommit($event));
    }

    /**
     * @test
     */
    public function it_check_if_is_one_stream_per_aggregate_strategy(): void
    {
        $streamName = new StreamName('customer');

        $streamProducer = new SingleStreamPerAggregate($streamName);

        $this->assertFalse($streamProducer->isOneStreamPerAggregate());
    }

    public function provideEvents(): Generator
    {
        yield [[]];
        yield [[SomeDomainEvent::fromContent(['steph' => 'bug'])]];
    }
}
