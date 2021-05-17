<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tracking;

use Chronhub\Chronicler\Exception\TransactionAlreadyStarted;
use Chronhub\Chronicler\Exception\TransactionNotStarted;
use Chronhub\Chronicler\Support\Contracts\Tracking\TransactionalContextualStream as TransactionalContext;

final class TransactionalContextualStream extends ContextualStream implements TransactionalContext
{
    public function hasTransactionNotStarted(): bool
    {
        return $this->exception instanceof TransactionNotStarted;
    }

    public function hasTransactionAlreadyStarted(): bool
    {
        return $this->exception instanceof TransactionAlreadyStarted;
    }
}
