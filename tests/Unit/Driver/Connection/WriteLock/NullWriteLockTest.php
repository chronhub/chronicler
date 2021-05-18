<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Driver\Connection\WriteLock;

use Chronhub\Chronicler\Driver\Connection\WriteLock\NoWriteLock;
use Chronhub\Chronicler\Tests\TestCase;
use Generator;

/** @coversDefaultClass \Chronhub\Chronicler\Driver\Connection\WriteLock\NoWriteLock */
final class NullWriteLockTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideTableName
     */
    public function it_always_acquire_lock(string $tableName): void
    {
        $lock = new NoWriteLock();

        $this->assertTrue($lock->acquireLock($tableName));
    }

    /**
     * @test
     * @dataProvider provideTableName
     */
    public function it_always_release_lock(string $tableName): void
    {
        $lock = new NoWriteLock();

        $this->assertTrue($lock->releaseLock($tableName));
    }

    public function provideTableName(): Generator
    {
        yield ['some_table'];
        yield ['another_table'];
    }
}
