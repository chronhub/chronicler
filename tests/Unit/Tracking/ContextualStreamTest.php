<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Tracking;

use Chronhub\Chronicler\Exception\ConcurrencyException;
use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\Exception\StreamAlreadyExists;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Chronicler\Tests\TestCase;
use Chronhub\Chronicler\Tracking\ContextualStream;
use Chronhub\Foundation\Aggregate\GenericAggregateId;
use Generator;

final class ContextualStreamTest extends TestCase
{
    /**
     * @test
     */
    public function it_test_aggregate_id(): void
    {
        $context = new ContextualStream(null);

        $aggregateId = GenericAggregateId::create();

        $context->withAggregateId($aggregateId);

        $this->assertEquals($aggregateId, $context->aggregateId());
    }

    /**
     * @test
     */
    public function it_test_stream(): void
    {
        $context = new ContextualStream(null);

        $stream = new Stream(new StreamName('customer_stream'));

        $context->withStream($stream);

        $this->assertEquals($stream, $context->stream());
    }

    /**
     * @test
     */
    public function it_test_stream_name(): void
    {
        $context = new ContextualStream(null);

        $streamName = new StreamName('customer_stream');

        $context->withStreamName($streamName);

        $this->assertEquals($streamName, $context->streamName());
    }

    /**
     * @test
     */
    public function it_test_stream_names(): void
    {
        $context = new ContextualStream(null);

        $streamNames = [
            new StreamName('customer_stream'),
            new StreamName('todo_stream'),
        ];

        $context->withStreamNames(...$streamNames);

        $this->assertEquals($streamNames, $context->streamNames());
    }

    /**
     * @test
     */
    public function it_test_categories_names(): void
    {
        $context = new ContextualStream(null);

        $categoryNames = [
            'customer-123',
            'customer-456',
        ];

        $context->withCategoryNames(...$categoryNames);

        $this->assertEquals($categoryNames, $context->categoryNames());
    }

    /**
     * @test
     */
    public function it_test_query_filter(): void
    {
        $context = new ContextualStream(null);

        $queryFilter = new class() implements QueryFilter {

            public function filterQuery(): callable
            {
                return function () { };
            }
        };

        $context->withQueryFilter($queryFilter);

        $this->assertEquals($queryFilter, $context->queryFilter());
    }

    /**
     * @test
     * @dataProvider provideDirection
     */
    public function it_test_direction(string $direction): void
    {
        $context = new ContextualStream(null);

        $context->withDirection($direction);

        $this->assertEquals($direction, $context->direction());
    }

    /**
     * @test
     * @dataProvider provideBoolean
     */
    public function it_test_stream_exists(bool $streamExists): void
    {
        $context = new ContextualStream(null);

        $context->setStreamExists($streamExists);

        $this->assertEquals($streamExists, $context->isStreamExists());
    }

    /**
     * @test
     */
    public function it_test_stream_not_found(): void
    {
        $exception = StreamNotFound::withStreamName(new StreamName('customer_stream'));

        $context = new ContextualStream(null);

        $context->withRaisedException($exception);

        $this->assertTrue($context->hasException());
        $this->assertTrue($context->hasStreamNotFound());
        $this->assertEquals($exception, $context->exception());
    }

    /**
     * @test
     */
    public function it_test_stream_already_exists(): void
    {
        $exception = StreamAlreadyExists::withStreamName(new StreamName('customer_stream'));

        $context = new ContextualStream(null);

        $this->assertFalse($context->hasStreamAlreadyExits());

        $context->withRaisedException($exception);

        $this->assertTrue($context->hasException());
        $this->assertTrue($context->hasStreamAlreadyExits());
        $this->assertEquals($exception, $context->exception());
    }

    /**
     * @test
     */
    public function it_test_concurrency_exception(): void
    {
        $exception = new ConcurrencyException("Stream failed");

        $context = new ContextualStream(null);

        $this->assertFalse($context->hasRaceCondition());

        $context->withRaisedException($exception);

        $this->assertTrue($context->hasException());
        $this->assertTrue($context->hasRaceCondition());
        $this->assertEquals($exception, $context->exception());
    }

    /**
     * @test
     */
    public function it_raise_exception_with_invalid_direction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Order by direction, allowed asc/desc, current is invalid_direction');

        $context = new ContextualStream(null);

        $context->withDirection('invalid_direction');
    }

    public function provideDirection(): Generator
    {
        yield ['asc'];
        yield ['desc'];
    }

    public function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
