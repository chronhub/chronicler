<?php

namespace Chronhub\Chronicler\Support\Contracts\Tracking;

interface TransactionalContextualStream extends ContextualStream
{
    /**
     * @return bool
     */
    public function hasTransactionNotStarted(): bool;

    /**
     * @return bool
     */
    public function hasTransactionAlreadyStarted(): bool;
}
