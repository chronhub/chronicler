<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tracking;

use Chronhub\Foundation\Tracker\HasTracker;
use Chronhub\Chronicler\Support\Contracts\Tracking\StreamTracker;
use Chronhub\Chronicler\Support\Contracts\Tracking\ContextualStream as Context;

class TrackStream implements StreamTracker
{
    use HasTracker;

    public function newContext(string $eventName): Context
    {
        return new ContextualStream($eventName);
    }
}
