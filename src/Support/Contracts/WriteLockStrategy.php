<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts;

interface WriteLockStrategy
{
    public function acquireLock(string $tableName): bool;

    public function releaseLock(string $tableName): bool;
}
