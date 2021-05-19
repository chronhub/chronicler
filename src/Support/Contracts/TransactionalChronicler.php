<?php

declare(strict_types=1);

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
     * @throws Throwable
     */
    public function transactional(callable $callback): mixed;

    public function inTransaction(): bool;
}
