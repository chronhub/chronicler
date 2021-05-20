<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit;

use Generator;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Chronhub\Chronicler\Exception\TransactionNotStarted;
use Chronhub\Chronicler\Tracking\TrackTransactionalStream;
use Chronhub\Chronicler\Exception\TransactionAlreadyStarted;
use Chronhub\Chronicler\GenericTransactionalEventChronicler;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;
use Chronhub\Chronicler\Support\Contracts\Tracking\ContextualStream;

/** @coversDefaultClass \Chronhub\Chronicler\GenericTransactionalEventChronicler */
/** @coversDefaultClass \Chronhub\Chronicler\ProvideEventsChronicle */
final class GenericTransactionalEventChroniclerTest extends TestCaseWithProphecy
{
    private TransactionalChronicler|ObjectProphecy $chronicler;
    private TrackTransactionalStream $tracker;

    protected function setUp(): void
    {
        $this->chronicler = $this->prophesize(TransactionalChronicler::class);
        $this->tracker = new TrackTransactionalStream();
    }

    /**
     * @test
     */
    public function it_dispatch_begin_transaction_event(): void
    {
        $this->chronicler->beginTransaction()->shouldBeCalled();

        $transactionalEventChronicler = new GenericTransactionalEventChronicler(
            $this->chronicler->reveal(), $this->tracker
        );

        $transactionalEventChronicler->beginTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_begin_transaction_event_and_raised_exception_if_transaction_already_started(): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $this->chronicler
            ->beginTransaction()
            ->willThrow(new TransactionAlreadyStarted())
            ->shouldBeCalled();

        $transactionalEventChronicler = new GenericTransactionalEventChronicler(
            $this->chronicler->reveal(), $this->tracker
        );

        $this->tracker->listen(TransactionalChronicler::BEGIN_TRANSACTION_EVENT,
            function (ContextualStream $context): void {
                $this->assertTrue($context->hasException());
            });

        $transactionalEventChronicler->beginTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_commit_transaction_event(): void
    {
        $this->chronicler->commitTransaction()->shouldBeCalled();

        $transactionalEventChronicler = new GenericTransactionalEventChronicler(
            $this->chronicler->reveal(), $this->tracker
        );

        $transactionalEventChronicler->commitTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_commit_transaction_event_and_raise_exception_if_exception_not_started(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler
            ->commitTransaction()
            ->willThrow(new TransactionNotStarted())
            ->shouldBeCalled();

        $transactionalEventChronicler = new GenericTransactionalEventChronicler(
            $this->chronicler->reveal(), $this->tracker
        );

        $this->tracker->listen(TransactionalChronicler::COMMIT_TRANSACTION_EVENT,
            function (ContextualStream $context): void {
                $this->assertTrue($context->hasException());
            });

        $transactionalEventChronicler->commitTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_rollback_transaction_event(): void
    {
        $this->chronicler->rollbackTransaction()->shouldBeCalled();

        $transactionalEventChronicler = new GenericTransactionalEventChronicler(
            $this->chronicler->reveal(), $this->tracker
        );

        $transactionalEventChronicler->rollbackTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_rollback_transaction_event_and_raise_exception_if_transaction_not_started(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler
            ->rollbackTransaction()
            ->willThrow(new TransactionNotStarted())
            ->shouldBeCalled();

        $transactionalEventChronicler = new GenericTransactionalEventChronicler(
            $this->chronicler->reveal(), $this->tracker
        );

        $this->tracker->listen(TransactionalChronicler::ROLLBACK_TRANSACTION_EVENT,
            function (ContextualStream $context): void {
                $this->assertTrue($context->hasException());
            });

        $transactionalEventChronicler->rollbackTransaction();
    }

    /**
     * @test
     * @dataProvider provideBoolean
     */
    public function it_check_if_in_transaction(bool $inTransaction): void
    {
        $this->chronicler
            ->inTransaction()
            ->willReturn($inTransaction)
            ->shouldBeCalled();

        $transactionalEventChronicler = new GenericTransactionalEventChronicler(
            $this->chronicler->reveal(), $this->tracker
        );

        $this->assertEquals($inTransaction, $transactionalEventChronicler->inTransaction());
    }

    /**
     * @test
     */
    public function it_send_callback_to_transaction(): void
    {
        $callback = function (): void { };

        $this->chronicler->transactional($callback)->shouldBeCalled();

        $transactionalEventChronicler = new GenericTransactionalEventChronicler(
            $this->chronicler->reveal(), $this->tracker
        );

        $transactionalEventChronicler->transactional($callback);
    }

    public function provideBoolean(): Generator
    {
        yield [true];

        yield [false];
    }
}
