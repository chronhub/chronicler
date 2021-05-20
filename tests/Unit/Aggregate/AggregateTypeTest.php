<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Aggregate;

use Generator;
use Chronhub\Chronicler\Tests\TestCase;
use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Foundation\Tests\Double\SomeNakedObject;
use Chronhub\Chronicler\Aggregate\GenericAggregateType;
use Chronhub\Chronicler\Tests\Double\SomeAggregateRoot;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Chronicler\Tests\Double\AnotherAggregateRoot;
use Chronhub\Chronicler\Tests\Double\SomeAggregateChanged;
use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\Tests\Double\InheritFromSomeAggregateRoot;

final class AggregateTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $aggregateType = new GenericAggregateType(SomeAggregateRoot::class);

        $this->assertEquals(SomeAggregateRoot::class, $aggregateType->aggregateRootClassName());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_aggregate_root_is_not_a_valid_class_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aggregate root must be a fqcn');

        new GenericAggregateType('invalid_class');
    }

    /**
     * @test
     */
    public function it_raise_exception_when_children_are_not_subclass_of_aggregate_root(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class ' . SomeNakedObject::class . ' must inherit from ' . SomeAggregateRoot::class);

        new GenericAggregateType(SomeAggregateRoot::class, [SomeNakedObject::class]);
    }

    /**
     * @test
     * @dataProvider provideValidAggregateTypeHeader
     */
    public function it_determine_supported_aggregate_root(string $aggregateTypeHeader): void
    {
        $aggregateType = new GenericAggregateType(SomeAggregateRoot::class, [InheritFromSomeAggregateRoot::class]);

        $aggregateChanged = SomeAggregateChanged::fromContent([])->withHeaders(
            [Header::AGGREGATE_TYPE => $aggregateTypeHeader]
        );

        /** @var AggregateChanged $aggregateChanged */
        $aggregateRoot = $aggregateType->determineFromEvent($aggregateChanged);

        $this->assertEquals($aggregateTypeHeader, $aggregateRoot);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_aggregate_root_is_not_supported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aggregate root ' . AnotherAggregateRoot::class . ' class is not supported');

        $aggregateType = new GenericAggregateType(SomeAggregateRoot::class, [InheritFromSomeAggregateRoot::class]);

        /** @var AggregateChanged $aggregateChanged */
        $aggregateChanged = SomeAggregateChanged::fromContent([])->withHeaders(
            [Header::AGGREGATE_TYPE => AnotherAggregateRoot::class]
        );

        $aggregateType->determineFromEvent($aggregateChanged);
    }

    public function provideValidAggregateTypeHeader(): Generator
    {
        yield [SomeAggregateRoot::class];
        yield [InheritFromSomeAggregateRoot::class];
    }
}
