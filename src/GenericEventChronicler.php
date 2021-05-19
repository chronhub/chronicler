<?php

declare(strict_types=1);

namespace Chronhub\Chronicler;

use Generator;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Foundation\Support\Contracts\Tracker\Listener;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Chronicler\Support\Contracts\EventableChronicler;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\Contracts\Tracking\StreamTracker;
use Chronhub\Foundation\Support\Contracts\Tracker\OneTimeListener;

class GenericEventChronicler implements EventableChronicler
{
    public function __construct(protected Chronicler $chronicler, protected StreamTracker $tracker)
    {
        ProvideEventsChronicle::withEvent($chronicler, $tracker);
    }

    public function persistFirstCommit(Stream $stream): void
    {
        $context = $this->tracker->newContext(self::FIRST_COMMIT_EVENT);

        $context->withStream($stream);

        $this->tracker->fire($context);

        if ($context->hasStreamAlreadyExits()) {
            throw $context->exception();
        }
    }

    public function persist(Stream $stream): void
    {
        $context = $this->tracker->newContext(self::PERSIST_STREAM_EVENT);

        $context->withStream($stream);

        $this->tracker->fire($context);

        if ($context->hasStreamNotFound() || $context->hasRaceCondition()) {
            throw $context->exception();
        }
    }

    public function delete(StreamName $streamName): void
    {
        $context = $this->tracker->newContext(self::DELETE_STREAM_EVENT);

        $context->withStreamName($streamName);

        $this->tracker->fire($context);

        if ($context->hasStreamNotFound()) {
            throw $context->exception();
        }
    }

    public function retrieveAll(StreamName $streamName, AggregateId $aggregateId, string $direction = 'asc'): Generator
    {
        $event = 'asc' === $direction ? self::ALL_STREAM_EVENT : self::ALL_REVERSED_STREAM_EVENT;

        $context = $this->tracker->newContext($event);

        $context->withStreamName($streamName);
        $context->withAggregateId($aggregateId);
        $context->withDirection($direction);

        $this->tracker->fire($context);

        if ($context->hasStreamNotFound()) {
            throw $context->exception();
        }

        return yield from $context->stream()->events();
    }

    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        $context = $this->tracker->newContext(self::FILTERED_STREAM_EVENT);

        $context->withStreamName($streamName);
        $context->withQueryFilter($queryFilter);

        $this->tracker->fire($context);

        if ($context->hasStreamNotFound()) {
            throw $context->exception();
        }

        return yield from $context->stream()->events();
    }

    public function fetchStreamNames(StreamName ...$streamNames): array
    {
        $context = $this->tracker->newContext(self::FETCH_STREAM_NAMES);

        $context->withStreamNames(...$streamNames);

        $this->tracker->fire($context);

        return $context->streamNames();
    }

    public function fetchCategoryNames(string ...$categoryNames): array
    {
        $context = $this->tracker->newContext(self::FETCH_CATEGORY_NAMES);

        $context->withCategoryNames(...$categoryNames);

        $this->tracker->fire($context);

        return $context->categoryNames();
    }

    public function hasStream(StreamName $streamName): bool
    {
        $context = $this->tracker->newContext(self::HAS_STREAM_EVENT);

        $context->withStreamName($streamName);

        $this->tracker->fire($context);

        return $context->isStreamExists();
    }

    public function subscribe(string $eventName, callable $eventContext, int $priority = 0): Listener
    {
        return $this->tracker->listen($eventName, $eventContext, $priority);
    }

    public function subscribeOnce(string $eventName, callable $eventContext, int $priority = 0): OneTimeListener
    {
        return $this->tracker->listenOnce($eventName, $eventContext, $priority);
    }

    public function unsubscribe(Listener ...$eventSubscribers): void
    {
        foreach ($eventSubscribers as $eventSubscriber) {
            $this->tracker->forget($eventSubscriber);
        }
    }

    public function innerChronicler(): Chronicler
    {
        return $this->chronicler;
    }
}
