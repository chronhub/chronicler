<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tracking;

use Chronhub\Chronicler\Support\Contracts\Tracking\TransactionalContextualStream as TransactionalContext;
use Chronhub\Chronicler\Support\Contracts\Tracking\TransactionalStreamTracker;

final class TrackTransactionalStream extends TrackStream implements TransactionalStreamTracker
{
    public function newContext(string $eventName): TransactionalContext
    {
        return new TransactionalContextualStream($eventName);
    }
}
