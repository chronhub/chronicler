<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Driver\Connection\WriteLock;

use Illuminate\Database\ConnectionInterface;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Chronhub\Chronicler\Driver\Connection\WriteLock\PgsqlWriteLock;

/** @coversDefaultClass \Chronhub\Chronicler\Driver\Connection\WriteLock\PgsqlWriteLock */
final class PgsqlWriteLockTest extends TestCaseWithProphecy
{
    /**
     * @test
     */
    public function it_acquire_lock(): void
    {
        $tableName = 'customer';
        $lockName = '_customer_write_lock';

        $connection = $this->prophesize(ConnectionInterface::class);
        $connection
            ->statement('select pg_advisory_lock( hashtext(\'' . $lockName . '\') )')
            ->willReturn(true)
            ->shouldBeCalled();

        $lock = new PgsqlWriteLock($connection->reveal());

        $this->assertTrue($lock->acquireLock($tableName));
    }

    /**
     * @test
     */
    public function it_release_lock(): void
    {
        $tableName = 'customer';
        $lockName = '_customer_write_lock';

        $connection = $this->prophesize(ConnectionInterface::class);
        $connection
            ->statement('select pg_advisory_unlock( hashtext(\'' . $lockName . '\') )')
            ->willReturn(true)
            ->shouldBeCalled();

        $lock = new PgsqlWriteLock($connection->reveal());

        $this->assertTrue($lock->releaseLock($tableName));
    }
}
