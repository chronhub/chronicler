<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tracking\Subscribers;

use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Support\Traits\RecordStreamEvent;
use Chronhub\Chronicler\Support\Contracts\EventableChronicler;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;
use Chronhub\Chronicler\Support\Contracts\Tracking\ContextualStream;
use Chronhub\Chronicler\Support\Contracts\Tracking\StreamSubscriber;

final class PublishEvents implements StreamSubscriber
{
    use RecordStreamEvent;

    public function attachToChronicler(Chronicler $chronicler): void
    {
        if ($chronicler instanceof EventableChronicler) {
            $this->subscribeToEventableChronicler($chronicler);

            if ($chronicler instanceof TransactionalChronicler) {
                $this->subscribeToTransactionalChronicler($chronicler);
            }
        }
    }

    private function subscribeToEventableChronicler(EventableChronicler $chronicler): void
    {
        $chronicler->subscribe($chronicler::FIRST_COMMIT_EVENT,
            function (ContextualStream $context) use ($chronicler): void {
                $streamEvents = $context->stream()->events();

                if ( ! $this->inTransaction($chronicler)) {
                    if ( ! $context->hasStreamAlreadyExits()) {
                        $this->publishEvents($streamEvents);
                    }
                } else {
                    $this->recordStreams($streamEvents);
                }
            });

        $chronicler->subscribe($chronicler::PERSIST_STREAM_EVENT,
            function (ContextualStream $context) use ($chronicler): void {
                $streamEvents = $context->stream()->events();

                if ( ! $this->inTransaction($chronicler)) {
                    if ( ! $context->hasStreamNotFound() && ! $context->hasRaceCondition()) {
                        $this->publishEvents($streamEvents);
                    }
                } else {
                    $this->recordStreams($streamEvents);
                }
            });
    }

    private function subscribeToTransactionalChronicler(EventableChronicler|TransactionalChronicler $chronicler): void
    {
        $chronicler->subscribe($chronicler::COMMIT_TRANSACTION_EVENT,
            function (): void {
                $recordedStreams = $this->pullRecords();
                $this->publishEvents($recordedStreams);
            });

        $chronicler->subscribe($chronicler::ROLLBACK_TRANSACTION_EVENT,
            function (): void {
                $this->clearRecords();
            });
    }

    private function inTransaction(Chronicler $chronicler): bool
    {
        return $chronicler instanceof TransactionalChronicler && $chronicler->inTransaction();
    }
}
