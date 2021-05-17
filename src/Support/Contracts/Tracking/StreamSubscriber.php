<?php

namespace Chronhub\Chronicler\Support\Contracts\Tracking;

use Chronhub\Chronicler\Support\Contracts\EventableChronicler;
use Chronhub\Foundation\Support\Contracts\Tracker\Subscriber;

interface StreamSubscriber extends Subscriber
{
    /**
     * @param EventableChronicler $chronicler
     */
    public function attachToChronicler(EventableChronicler $chronicler): void;
}
