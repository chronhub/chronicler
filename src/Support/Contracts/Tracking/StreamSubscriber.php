<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Tracking;

use Chronhub\Foundation\Support\Contracts\Tracker\Subscriber;
use Chronhub\Chronicler\Support\Contracts\EventableChronicler;

interface StreamSubscriber extends Subscriber
{
    public function attachToChronicler(EventableChronicler $chronicler): void;
}
