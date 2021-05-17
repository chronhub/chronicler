<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Aggregate;

use Chronhub\Chronicler\Aggregate\AggregateEventReleaser;
use Chronhub\Chronicler\Tests\Double\SomeAggregateChanged;
use Chronhub\Chronicler\Tests\Double\SomeAggregateRoot;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Chronhub\Foundation\Aggregate\GenericAggregateId;
use Chronhub\Foundation\Message\Message;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Foundation\Support\Contracts\Message\MessageDecorator;
use Chronhub\Foundation\Support\NoOpMessageDecorator;
use Generator;
use Prophecy\Argument;
use function reset;

/** @coversDefaultClass \Chronhub\Chronicler\Aggregate\AggregateEventReleaser */
final class AggregateEventReleaserTest extends TestCaseWithProphecy
{
    /**
     * @test
     */
    public function it_release_domain_events_from_aggregate_root(): void
    {
        $aggregateId = GenericAggregateId::create();

        $events = iterator_to_array($this->provideEvents($aggregateId, 2));

        $aggregateRoot = SomeAggregateRoot::create($aggregateId, $events);

        $this->assertEquals(2, $aggregateRoot->countEventsToRelease());

        $builder = new AggregateEventReleaser(new NoOpMessageDecorator());

        $events = $builder->releaseEvents($aggregateRoot);

        $this->assertCount(2, $events);
        $this->assertEquals(2, $aggregateRoot->version());
        $this->assertEquals(0, $aggregateRoot->countEventsToRelease());
    }

    /**
     * @test
     */
    public function it_update_domain_event_header(): void
    {
        $aggregateId = GenericAggregateId::create();

        $events = iterator_to_array($this->provideEvents($aggregateId, 5));

        $aggregateRoot = SomeAggregateRoot::create($aggregateId, $events);

        $builder = new AggregateEventReleaser(new NoOpMessageDecorator());

        $events = $builder->releaseEvents($aggregateRoot);

        $this->assertEquals(1, reset($events)->headers()[Header::AGGREGATE_VERSION]);

        foreach ($events as $event) {
            $this->assertArrayHasKey(Header::AGGREGATE_ID, $event->headers());
            $this->assertArrayHasKey(Header::AGGREGATE_ID_TYPE, $event->headers());
            $this->assertArrayHasKey(Header::AGGREGATE_TYPE, $event->headers());

            $this->assertEquals($aggregateId->toString(), $event->headers()[Header::AGGREGATE_ID]);
            $this->assertEquals($aggregateId::class, $event->headers()[Header::AGGREGATE_ID_TYPE]);
            $this->assertEquals($aggregateRoot::class, $event->headers()[Header::AGGREGATE_TYPE]);
        }
    }

    /**
     * @test
     */
    public function it_decorate_domain_events(): void
    {
        $messageDecorator = $this->prophesize(MessageDecorator::class);

        $messageDecorator->decorate(Argument::type(Message::class))
            ->will(function (array $messages): Message {
                $message = array_shift($messages);

                return $message->withHeader('some_header', true);
            })->shouldBeCalledTimes(2);

        $aggregateId = GenericAggregateId::create();
        $events = iterator_to_array($this->provideEvents($aggregateId, 2));
        $aggregateRoot = SomeAggregateRoot::create($aggregateId, $events);

        $builder = new AggregateEventReleaser($messageDecorator->reveal());

        $events = $builder->releaseEvents($aggregateRoot);

        foreach ($events as $event) {
            $this->assertArrayHasKey('some_header', $event->headers());
            $this->assertTrue($event->headers()['some_header']);
        }
    }

    /**
     * @test
     */
    public function it_return_empty_array_if_release_events_is_empty(): void
    {
        $messageDecorator = $this->prophesize(MessageDecorator::class);
        $messageDecorator->decorate(Argument::type(Message::class))->shouldNotBeCalled();

        $aggregateRoot = $this->prophesize(AggregateRoot::class);
        $aggregateRoot->releaseEvents()->willReturn([])->shouldBeCalled();
        $aggregateRoot->version()->shouldNotBeCalled();
        $aggregateRoot->aggregateId()->shouldNotBeCalled();

        $builder = new AggregateEventReleaser($messageDecorator->reveal());

        $this->assertEmpty($builder->releaseEvents($aggregateRoot->reveal()));
    }

    private function provideEvents(AggregateId $aggregateId, int $limit): Generator
    {
        $return = $limit;

        while ($limit !== 0) {
            yield SomeAggregateChanged::withData($aggregateId, []);

            $limit--;
        }

        return $return;
    }
}
