<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tracking\Subscribers;

use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Foundation\Support\Contracts\Reporter\Reporter;
use Chronhub\Foundation\Support\Contracts\Tracker\MessageTracker;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;
use Chronhub\Foundation\Support\Contracts\Tracker\ContextualMessage;
use Chronhub\Foundation\Support\Contracts\Tracker\MessageSubscriber;

final class TransactionalHandlerSubscriber implements MessageSubscriber
{
    public function __construct(private Chronicler $chronicler)
    {
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $tracker->listen(Reporter::DISPATCH_EVENT, function (): void {
            if ($this->chronicler instanceof TransactionalChronicler) {
                $this->chronicler->beginTransaction();
            }
        }, Reporter::PRIORITY_INVOKE_HANDLER + 1000);

        $tracker->listen(Reporter::FINALIZE_EVENT, function (ContextualMessage $context): void {
            if ($this->chronicler instanceof TransactionalChronicler && $this->chronicler->inTransaction()) {
                $context->hasException()
                    ? $this->chronicler->rollbackTransaction()
                    : $this->chronicler->commitTransaction();
            }
        }, 1000);
    }
}
