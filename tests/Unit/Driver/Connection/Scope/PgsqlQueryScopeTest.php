<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Driver\Connection\Scope;

use Generator;
use Illuminate\Database\Query\Builder;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\Driver\Connection\Scope\PgsqlQueryScope;

final class PgsqlQueryScopeTest extends TestCaseWithProphecy
{
    /**
     * @test
     * @dataProvider provideDirection
     */
    public function it_match_aggregate_id_and_type_greater_than_version(string $direction): void
    {
        $builder = $this->prophesize(Builder::class);

        $builder
            ->whereJsonContains('headers->__aggregate_id', 'id')
            ->willReturn($builder)
            ->shouldBeCalled();

        $builder->whereJsonContains('headers->__aggregate_type', 'type')
            ->willReturn($builder)
            ->shouldBeCalled();

        $builder
            ->whereRaw('CAST(headers->>\'__aggregate_version\' AS INT) > 1')
            ->willReturn($builder)
            ->shouldBeCalled();

        $builder
            ->orderByRaw('CAST(headers->>\'__aggregate_version\' AS INT) ' . $direction)
            ->willReturn($builder)
            ->shouldBeCalled();

        $scope = new PgsqlQueryScope();

        $filter = $scope->matchAggregateGreaterThanVersion('id', 'type', 1, $direction);

        $filter->filterQuery()($builder->reveal());
    }

    /**
     * @test
     * @dataProvider provideInvalidAggregateVersion
     */
    public function it_raise_exception_if_current_version_less_than_0(int $invalidVersion): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Aggregate version must be greater or equals than 0, current is $invalidVersion");

        $builder = $this->prophesize(Builder::class);

        $scope = new PgsqlQueryScope();

        $filter = $scope->matchAggregateGreaterThanVersion('id', 'type', $invalidVersion);

        $filter->filterQuery()($builder->reveal());
    }

    public function provideInvalidAggregateVersion(): Generator
    {
        yield [-1];
        yield [-5];
    }

    public function provideDirection(): Generator
    {
        yield ['asc'];
        yield ['desc'];
    }
}
