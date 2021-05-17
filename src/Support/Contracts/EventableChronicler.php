<?php

namespace Chronhub\Chronicler\Support\Contracts;

use Chronhub\Foundation\Support\Contracts\Tracker\Listener;
use Chronhub\Foundation\Support\Contracts\Tracker\OneTimeListener;

interface EventableChronicler extends ChroniclerDecorator
{
    public const FIRST_COMMIT_EVENT = 'first_commit_stream_event';
    public const PERSIST_STREAM_EVENT = 'persist_stream_event';
    public const DELETE_STREAM_EVENT = 'delete_stream_event';
    public const ALL_STREAM_EVENT = 'all_stream_event';
    public const ALL_REVERSED_STREAM_EVENT = 'all_reversed_stream_event';
    public const FILTERED_STREAM_EVENT = 'filtered_stream_event';
    public const FETCH_STREAM_NAMES = 'fetch_stream_names_event';
    public const FETCH_CATEGORY_NAMES = 'fetch_category_names_event';
    public const HAS_STREAM_EVENT = 'has_stream_event';

    /**
     * @param string   $eventName
     * @param callable $eventContext
     * @param int      $priority
     * @return Listener
     */
    public function subscribe(string $eventName, callable $eventContext, int $priority = 0): Listener;

    /**
     * @param string   $eventName
     * @param callable $eventContext
     * @param int      $priority
     * @return OneTimeListener
     */
    public function subscribeOnce(string $eventName, callable $eventContext, int $priority = 0): OneTimeListener;

    /**
     * @param Listener ...$eventSubscribers
     */
    public function unsubscribe(Listener ...$eventSubscribers): void;
}
