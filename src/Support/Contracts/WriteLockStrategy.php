<?php

namespace Chronhub\Chronicler\Support\Contracts;

interface WriteLockStrategy
{
    /**
     * @param string $tableName
     * @return bool
     */
    public function acquireLock(string $tableName): bool;

    /**
     * @param string $tableName
     * @return bool
     */
    public function releaseLock(string $tableName): bool;
}
