<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Tracking;

use Chronhub\Chronicler\Tests\TestCase;
use Chronhub\Chronicler\Exception\TransactionNotStarted;
use Chronhub\Chronicler\Exception\TransactionAlreadyStarted;
use Chronhub\Chronicler\Tracking\TransactionalContextualStream;

final class TransactionalContextualStreamTest extends TestCase
{
    /**
     * @test
     */
    public function it_test_transaction_not_started(): void
    {
        $exception = new TransactionNotStarted('transaction not started');

        $context = new TransactionalContextualStream(null);

        $this->assertFalse($context->hasTransactionNotStarted());

        $context->withRaisedException($exception);

        $this->assertTrue($context->hasException());
        $this->assertTrue($context->hasTransactionNotStarted());
        $this->assertEquals($exception, $context->exception());
    }

    /**
     * @test
     */
    public function it_test_transaction_already_started(): void
    {
        $exception = new TransactionAlreadyStarted('transaction already started');

        $context = new TransactionalContextualStream(null);

        $this->assertFalse($context->hasTransactionAlreadyStarted());

        $context->withRaisedException($exception);

        $this->assertTrue($context->hasException());
        $this->assertTrue($context->hasTransactionAlreadyStarted());
        $this->assertEquals($exception, $context->exception());
    }
}
