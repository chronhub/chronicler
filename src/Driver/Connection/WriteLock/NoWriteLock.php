<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\WriteLock;

use Chronhub\Chronicler\Support\Contracts\WriteLockStrategy;

final class NoWriteLock implements WriteLockStrategy
{
    public function acquireLock(string $tableName): bool
    {
        return true;
    }

    public function releaseLock(string $tableName): bool
    {
        return true;
    }
}
