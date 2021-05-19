<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tracking;

use Chronhub\Chronicler\Support\Contracts\Tracking\TransactionalStreamTracker;
use Chronhub\Chronicler\Support\Contracts\Tracking\TransactionalContextualStream as TransactionalContext;

final class TrackTransactionalStream extends TrackStream implements TransactionalStreamTracker
{
    public function newContext(string $eventName): TransactionalContext
    {
        return new TransactionalContextualStream($eventName);
    }
}
