<?php

namespace Chronhub\Chronicler\Support\Contracts;

use Throwable;

interface TransactionalChronicler extends Chronicler
{
    public const BEGIN_TRANSACTION_EVENT = 'begin_transaction_event';
    public const COMMIT_TRANSACTION_EVENT = 'commit_transaction_event';
    public const ROLLBACK_TRANSACTION_EVENT = 'rollback_transaction_event';

    /**
     * @throws Throwable
     */
    public function beginTransaction(): void;

    /**
     * @throws Throwable
     */
    public function commitTransaction(): void;

    /**
     * @throws Throwable
     */
    public function rollbackTransaction(): void;

    /**
     * @return bool
     */
    public function inTransaction(): bool;

    /**
     * @param callable $callback
     * @return mixed
     * @throws Throwable
     */
    public function transactional(callable $callback): mixed;
}
