<?php
declare(strict_types=1);

namespace Chronhub\Chronicler;

use Chronhub\Chronicler\Support\Contracts\Tracking\TransactionalStreamTracker;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;

final class GenericTransactionalEventChronicler extends GenericEventChronicler implements TransactionalChronicler
{
    public function __construct(TransactionalChronicler $chronicler, TransactionalStreamTracker $tracker)
    {
        parent::__construct($chronicler, $tracker);

        ProvideEventsChronicle::withTransactionalEvent($chronicler, $tracker);
    }

    public function beginTransaction(): void
    {
        $context = $this->tracker->newContext(self::BEGIN_TRANSACTION_EVENT);

        $this->tracker->fire($context);

        if ($context->hasTransactionAlreadyStarted()) {
            throw $context->exception();
        }
    }

    public function commitTransaction(): void
    {
        $context = $this->tracker->newContext(self::COMMIT_TRANSACTION_EVENT);

        $this->tracker->fire($context);

        if ($context->hasTransactionNotStarted()) {
            throw $context->exception();
        }
    }

    public function rollbackTransaction(): void
    {
        $context = $this->tracker->newContext(self::ROLLBACK_TRANSACTION_EVENT);

        $this->tracker->fire($context);

        if ($context->hasTransactionNotStarted()) {
            throw $context->exception();
        }
    }

    public function inTransaction(): bool
    {
        return $this->chronicler->inTransaction();
    }

    public function transactional(callable $callback): mixed
    {
        return $this->chronicler->transactional($callback);
    }
}
