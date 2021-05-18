<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Tracking\Subscribers;

use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\Exception\RuntimeException;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Support\Contracts\InMemoryChronicler;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;
use Chronhub\Chronicler\Tests\Double\SomeDomainEvent;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Chronhub\Chronicler\Tracking\Subscribers\PublishTransactionalInMemoryEvents;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Reporter\ReportEvent;
use Chronhub\Foundation\Support\Contracts\Reporter\Reporter;
use Chronhub\Foundation\Tracker\TrackMessage;
use Prophecy\Argument;

final class PublishTransactionalInMemoryEventsTest extends TestCaseWithProphecy
{
    /**
     * @test
     */
    public function it_raise_exception_with_invalid_chronicler_on_construction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message subscriber does not support chronicler type');

        $chronicler = $this->prophesize(Chronicler::class)->reveal();
        $reporter = $this->prophesize(ReportEvent::class)->reveal();

        new PublishTransactionalInMemoryEvents($chronicler, $reporter);
    }

    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $chronicler = $this->prophesize(Chronicler::class);
        $chronicler
            ->willImplement(InMemoryChronicler::class)
            ->willImplement(TransactionalChronicler::class);

        $reporter = $this->prophesize(ReportEvent::class)->reveal();
        new PublishTransactionalInMemoryEvents($chronicler->reveal(), $reporter);

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_dispatch_recorded_events(): void
    {
        $chronicler = $this->prophesize(Chronicler::class);
        $chronicler
            ->willImplement(InMemoryChronicler::class)
            ->willImplement(TransactionalChronicler::class);

        $chronicler->pullCachedRecordedEvents()->willReturn(
            [
                SomeDomainEvent::fromContent(['name' => 'steph']),
                SomeDomainEvent::fromContent(['name' => 'bug']),
                SomeDomainEvent::fromContent(['name' => 'fab']),
            ]
        )->shouldBeCalled();

        $reporter = $this->prophesize(ReportEvent::class);
        $reporter->publish(Argument::type(DomainEvent::class))->shouldBeCalledTimes(3);

        $subscriber = new PublishTransactionalInMemoryEvents($chronicler->reveal(), $reporter->reveal());

        $tracker = new TrackMessage();
        $context = $tracker->newContext(Reporter::FINALIZE_EVENT);
        $this->assertFalse($context->hasException());

        $subscriber->attachToTracker($tracker);

        $tracker->fire($context);
    }

    /**
     * @test
     */
    public function it_does_not_dispatch_recorded_events_when_context_has_exception(): void
    {
        $chronicler = $this->prophesize(Chronicler::class);
        $chronicler
            ->willImplement(InMemoryChronicler::class)
            ->willImplement(TransactionalChronicler::class);

        $chronicler->pullCachedRecordedEvents()->shouldNotBeCalled();

        $reporter = $this->prophesize(ReportEvent::class);
        $reporter->publish(Argument::type(DomainEvent::class))->shouldNotBeCalled();

        $subscriber = new PublishTransactionalInMemoryEvents($chronicler->reveal(), $reporter->reveal());

        $tracker = new TrackMessage();
        $context = $tracker->newContext(Reporter::FINALIZE_EVENT);
        $context->withRaisedException(new RuntimeException('failed'));

        $subscriber->attachToTracker($tracker);

        $tracker->fire($context);
    }
}
