<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tracking\Subscribers;

use Chronhub\Foundation\Reporter\ReportEvent;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Foundation\Support\Contracts\Reporter\Reporter;
use Chronhub\Chronicler\Support\Contracts\InMemoryChronicler;
use Chronhub\Chronicler\Support\Contracts\EventableChronicler;
use Chronhub\Foundation\Support\Contracts\Tracker\MessageTracker;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;
use Chronhub\Foundation\Support\Contracts\Tracker\ContextualMessage;
use Chronhub\Foundation\Support\Contracts\Tracker\MessageSubscriber;

final class PublishTransactionalInMemoryEvents implements MessageSubscriber
{
    public function __construct(private Chronicler $chronicler,
                                private ReportEvent $reporter)
    {
        $this->assertIsSupported($chronicler);
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $tracker->listen(Reporter::FINALIZE_EVENT,
            function (ContextualMessage $context): void {
                if ($context->hasException()) {
                    return;
                }

                foreach ($this->chronicler->pullCachedRecordedEvents() as $recordedEvent) {
                    $this->reporter->publish($recordedEvent);
                }
            }, -2000);
    }

    private function assertIsSupported(Chronicler $chronicler): void
    {
        if ($chronicler instanceof EventableChronicler ||
            ( ! $this->chronicler instanceof InMemoryChronicler
                && ! $this->chronicler instanceof TransactionalChronicler)
        ) {
            throw new InvalidArgumentException('Message subscriber does not support chronicler type');
        }
    }
}
