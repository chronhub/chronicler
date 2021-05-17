<?php
declare(strict_types=1);

namespace Chronhub\Chronicler;

use Chronhub\Chronicler\Exception\ConcurrencyException;
use Chronhub\Chronicler\Exception\StreamAlreadyExists;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\Exception\TransactionAlreadyStarted;
use Chronhub\Chronicler\Exception\TransactionNotStarted;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Support\Contracts\EventableChronicler;
use Chronhub\Chronicler\Support\Contracts\Tracking\ContextualStream;
use Chronhub\Chronicler\Support\Contracts\Tracking\StreamTracker;
use Chronhub\Chronicler\Support\Contracts\Tracking\TransactionalContextualStream;
use Chronhub\Chronicler\Support\Contracts\Tracking\TransactionalStreamTracker;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;

final class ProvideEventsChronicle
{
    public static function withEvent(Chronicler $chronicler, StreamTracker $tracker): void
    {
        $tracker->listen(EventableChronicler::FIRST_COMMIT_EVENT,
            function (ContextualStream $contextEvent) use ($chronicler): void {
                try {
                    $chronicler->persistFirstCommit($contextEvent->stream());
                } catch (StreamAlreadyExists $exception) {
                    $contextEvent->withRaisedException($exception);
                }
            });

        $tracker->listen(EventableChronicler::PERSIST_STREAM_EVENT,
            function (ContextualStream $context) use ($chronicler): void {
                try {
                    $chronicler->persist($context->stream());
                } catch (StreamNotFound | ConcurrencyException $exception) {
                    $context->withRaisedException($exception);
                }
            });

        $tracker->listen(EventableChronicler::DELETE_STREAM_EVENT,
            function (ContextualStream $context) use ($chronicler): void {
                try {
                    $chronicler->delete($context->streamName());
                } catch (StreamNotFound $exception) {
                    $context->withRaisedException($exception);
                }
            });

        $tracker->listen(EventableChronicler::ALL_STREAM_EVENT,
            function (ContextualStream $context) use ($chronicler): void {
                try {
                    $streamEvents = $chronicler->retrieveAll(
                        $context->streamName(),
                        $context->aggregateId(),
                        $context->direction()
                    );

                    $newStream = new Stream($context->streamName(), $streamEvents);

                    $context->withStream($newStream);
                } catch (StreamNotFound $exception) {
                    $context->withRaisedException($exception);
                }
            });

        $tracker->listen(EventableChronicler::ALL_REVERSED_STREAM_EVENT,
            function (ContextualStream $context) use ($chronicler): void {
                try {
                    $streamEvents = $chronicler->retrieveAll(
                        $context->streamName(),
                        $context->aggregateId(),
                        $context->direction()
                    );

                    $newStream = new Stream($context->streamName(), $streamEvents);

                    $context->withStream($newStream);
                } catch (StreamNotFound $exception) {
                    $context->withRaisedException($exception);
                }
            });

        $tracker->listen(EventableChronicler::FILTERED_STREAM_EVENT,
            function (ContextualStream $context) use ($chronicler): void {
                try {
                    $streamName = $context->streamName();

                    $stream = new Stream(
                        $streamName,
                        $chronicler->retrieveFiltered($streamName, $context->queryFilter())
                    );

                    $context->withStream($stream);
                } catch (StreamNotFound $exception) {
                    $context->withRaisedException($exception);
                }
            });

        $tracker->listen(EventableChronicler::FETCH_STREAM_NAMES,
            function (ContextualStream $context) use ($chronicler): void {
                $streamNames = $chronicler->fetchStreamNames(...$context->streamNames());

                $context->withStreamNames(...$streamNames);
            });

        $tracker->listen(EventableChronicler::FETCH_CATEGORY_NAMES,
            function (ContextualStream $context) use ($chronicler): void {
                $categoryNames = $chronicler->fetchCategoryNames(
                    ...$context->categoryNames()
                );

                $context->withCategoryNames(...$categoryNames);
            });

        $tracker->listen(EventableChronicler::HAS_STREAM_EVENT,
            function (ContextualStream $context) use ($chronicler): void {
                $streamExists = $chronicler->hasStream($context->streamName());

                $context->setStreamExists($streamExists);
            });
    }

    public static function withTransactionalEvent(TransactionalChronicler $chronicler,
                                                  TransactionalStreamTracker $tracker): void
    {
        $tracker->listen(TransactionalChronicler::BEGIN_TRANSACTION_EVENT,
            function (TransactionalContextualStream $context) use ($chronicler): void {
                try {
                    $chronicler->beginTransaction();
                } catch (TransactionAlreadyStarted $exception) {
                    $context->withRaisedException($exception);
                }
            });

        $tracker->listen(TransactionalChronicler::COMMIT_TRANSACTION_EVENT,
            function (TransactionalContextualStream $context) use ($chronicler): void {
                try {
                    $chronicler->commitTransaction();
                } catch (TransactionNotStarted $exception) {
                    $context->withRaisedException($exception);
                }
            });

        $tracker->listen(TransactionalChronicler::ROLLBACK_TRANSACTION_EVENT,
            function (TransactionalContextualStream $context) use ($chronicler): void {
                try {
                    $chronicler->rollbackTransaction();
                } catch (TransactionNotStarted $exception) {
                    $context->withRaisedException($exception);
                }
            });
    }
}
