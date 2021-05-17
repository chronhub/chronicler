<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Tracking\Subscribers;

use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Chronhub\Chronicler\Tracking\Subscribers\TransactionalHandlerSubscriber;
use Chronhub\Foundation\Support\Contracts\Reporter\Reporter;
use Chronhub\Foundation\Tracker\TrackMessage;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

final class TransactionalHandlerSubscriberTest extends TestCaseWithProphecy
{
    private TransactionalChronicler|ObjectProphecy $chronicler;

    public function setup(): void
    {
        $this->chronicler = $this->prophesize(TransactionalChronicler::class);
    }

    /**
     * @test
     */
    public function it_begin_transaction_on_dispatch_command(): void
    {
        $this->chronicler->beginTransaction()->shouldBeCalled();

        $subscriber = new TransactionalHandlerSubscriber($this->chronicler->reveal());

        $tracker = new TrackMessage();
        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);

        $subscriber->attachToTracker($tracker);

        $tracker->fire($context);
    }

    /**
     * @test
     */
    public function it_does_not_begin_transaction_if_event_store_not_instance_of_transactional_chronicler(): void
    {
        $chronicler = $this->prophesize(Chronicler::class);

        $subscriber = new TransactionalHandlerSubscriber($chronicler->reveal());

        $tracker = new TrackMessage();
        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);

        $subscriber->attachToTracker($tracker);

        $tracker->fire($context);

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_commit_transaction_on_finalize_when_no_exception_found_in_context_and_chronicler_in_transaction(): void
    {
        $this->chronicler->inTransaction()->willReturn(true)->shouldBeCalled();

        $this->chronicler->commitTransaction()->shouldBeCalled();
        $subscriber = new TransactionalHandlerSubscriber($this->chronicler->reveal());

        $tracker = new TrackMessage();
        $context = $tracker->newContext(Reporter::FINALIZE_EVENT);
        $this->assertFalse($context->hasException());

        $subscriber->attachToTracker($tracker);

        $tracker->fire($context);
    }

    /**
     * @test
     */
    public function it_does_not_commit_transaction_when_chronicler_not_in_transaction(): void
    {
        $this->chronicler->inTransaction()->willReturn(false)->shouldBeCalled();

        $this->chronicler->commitTransaction()->shouldNotBeCalled();
        $subscriber = new TransactionalHandlerSubscriber($this->chronicler->reveal());

        $tracker = new TrackMessage();
        $context = $tracker->newContext(Reporter::FINALIZE_EVENT);
        $this->assertFalse($context->hasException());

        $subscriber->attachToTracker($tracker);

        $tracker->fire($context);
    }

    /**
     * @test
     */
    public function it_rollback_transaction_when_context_has_exception(): void
    {
        $this->chronicler->inTransaction()->willReturn(true)->shouldBeCalled();
        $this->chronicler->commitTransaction()->shouldNotBeCalled();
        $this->chronicler->rollbackTransaction()->shouldBeCalled();

        $subscriber = new TransactionalHandlerSubscriber($this->chronicler->reveal());

        $tracker = new TrackMessage();
        $context = $tracker->newContext(Reporter::FINALIZE_EVENT);
        $context->withRaisedException(new RuntimeException('failed'));

        $subscriber->attachToTracker($tracker);

        $tracker->fire($context);
    }

    /**
     * @test
     */
    public function it_does_not_commit_transaction_if_event_store_not_instance_of_transactional_chronicler(): void
    {
        $chronicler = $this->prophesize(Chronicler::class)->reveal();

        $subscriber = new TransactionalHandlerSubscriber($chronicler);

        $tracker = new TrackMessage();
        $context = $tracker->newContext(Reporter::FINALIZE_EVENT);
        $this->assertFalse($context->hasException());

        $subscriber->attachToTracker($tracker);

        $tracker->fire($context);
    }
}
