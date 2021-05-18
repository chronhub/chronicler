<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Traits;

use Chronhub\Chronicler\Exception\TransactionAlreadyStarted;
use Chronhub\Chronicler\Exception\TransactionNotStarted;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Throwable;

trait HasConnectionTransaction
{
    protected ConnectionInterface|Connection $connection;

    public function beginTransaction(): void
    {
        try {
            $this->connection->beginTransaction();
        } catch (Throwable) {
            throw new TransactionAlreadyStarted('Transaction already started');
        }
    }

    public function commitTransaction(): void
    {
        try {
            $this->connection->commit();
        } catch (Throwable) {
            throw new TransactionNotStarted('Transaction not started');
        }
    }

    public function rollbackTransaction(): void
    {
        try {
            $this->connection->rollBack();
        } catch (Throwable) {
            throw new TransactionNotStarted('Transaction not started');
        }
    }

    public function inTransaction(): bool
    {
        return $this->connection->transactionLevel() > 0;
    }

    public function transactional(callable $callable): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callable($this);

            $this->commitTransaction();
        } catch (Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }

        return $result ?: true;
    }
}
