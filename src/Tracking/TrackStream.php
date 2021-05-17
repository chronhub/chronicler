<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tracking;

use Chronhub\Chronicler\Support\Contracts\Tracking\ContextualStream as Context;
use Chronhub\Chronicler\Support\Contracts\Tracking\StreamTracker;
use Chronhub\Foundation\Tracker\HasTracker;

class TrackStream implements StreamTracker
{
    use HasTracker;

    public function newContext(string $eventName): Context
    {
        return new ContextualStream($eventName);
    }
}
