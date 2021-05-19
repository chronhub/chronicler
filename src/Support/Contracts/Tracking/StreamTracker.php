<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Tracking;

use Chronhub\Foundation\Support\Contracts\Tracker\Tracker;

interface StreamTracker extends Tracker
{
    public function newContext(string $eventName): ContextualStream;
}
