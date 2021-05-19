<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tracking\Subscribers;

use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Support\Contracts\EventableChronicler;
use Chronhub\Chronicler\Support\Contracts\Tracking\ContextualStream;
use Chronhub\Foundation\Message\DomainCommand;
use Chronhub\Foundation\Message\Message;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Foundation\Support\Contracts\Message\MessageDecorator;
use Chronhub\Foundation\Support\Contracts\Reporter\Reporter;
use Chronhub\Foundation\Support\Contracts\Tracker\ContextualMessage;
use Chronhub\Foundation\Support\Contracts\Tracker\MessageSubscriber;
use Chronhub\Foundation\Support\Contracts\Tracker\MessageTracker;
use Chronhub\Foundation\Support\Contracts\Tracker\OneTimeListener;

final class MarkCausationCommand implements MessageSubscriber
{
    private array $oneTimeListeners = [];

    /**
     * MarkCausationCommand constructor.
     *
     * @param Chronicler|EventableChronicler $chronicler
     */
    public function __construct(private Chronicler $chronicler)
    {
        //
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        if (!$this->chronicler instanceof EventableChronicler) {
            return;
        }

        $tracker->listen(Reporter::DISPATCH_EVENT,
            function (ContextualMessage $context): void {
                $command = $this->determineCommand($context->message());

                $callback = function (ContextualStream $stream) use ($command): void {
                    if ($command) {
                        $messageDecorator = $this->correlationMessageDecorator($command);

                        $stream->decorateStreamEvents($messageDecorator);
                    }
                };

                if ($command) {
                    $this->oneTimeListeners[] = $this->subscribeOnFirstCommitEvent($callback);

                    $this->oneTimeListeners[] = $this->subscribeOnPersistStreamEvent($callback);
                }
            }, 1000);

        $tracker->listen(Reporter::FINALIZE_EVENT,
            function (): void {
                $this->chronicler->unsubscribe(...$this->oneTimeListeners);
                $this->oneTimeListeners = [];
            }, 1000);
    }

    private function subscribeOnPersistStreamEvent(callable $callback): OneTimeListener
    {
        return $this->chronicler->subscribeOnce(
            EventableChronicler::PERSIST_STREAM_EVENT, $callback, 1000
        );
    }

    private function subscribeOnFirstCommitEvent(callable $callback): OneTimeListener
    {
        return $this->chronicler->subscribeOnce(
            EventableChronicler::FIRST_COMMIT_EVENT, $callback, 1000
        );
    }

    private function determineCommand(Message $message): ?DomainCommand
    {
        $event = $message->event();

        return $event instanceof DomainCommand ? $event : null;
    }

    private function correlationMessageDecorator(DomainCommand $command): MessageDecorator
    {
        $eventId = $command->header(Header::EVENT_ID)->toString();
        $eventType = $command->header(Header::EVENT_TYPE);

        return new class($eventId, $eventType) implements MessageDecorator {
            private string $eventId;
            private string $eventType;

            public function __construct(string $eventId, string $eventType)
            {
                $this->eventId = $eventId;
                $this->eventType = $eventType;
            }

            public function decorate(Message $message): Message
            {
                if ($message->has(Header::EVENT_CAUSATION_ID)
                    && $message->has(Header::EVENT_CAUSATION_TYPE)) {
                    return $message;
                }

                return $message
                    ->withHeader(Header::EVENT_CAUSATION_ID, $this->eventId)
                    ->withHeader(Header::EVENT_CAUSATION_TYPE, $this->eventType);
            }
        };
    }
}
