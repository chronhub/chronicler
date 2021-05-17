<?php

namespace Chronhub\Chronicler\Support\Contracts\Tracking;

use Chronhub\Foundation\Support\Contracts\Tracker\Tracker;

interface StreamTracker extends Tracker
{
    /**
     * @param string $eventName
     * @return ContextualStream
     */
    public function newContext(string $eventName): ContextualStream;
}
