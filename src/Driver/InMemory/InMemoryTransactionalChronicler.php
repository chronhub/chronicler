<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\InMemory;

use Chronhub\Chronicler\Exception\StreamAlreadyExists;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Chronicler\Exception\TransactionAlreadyStarted;
use Chronhub\Chronicler\Exception\TransactionNotStarted;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Contracts\Model\EventStreamProvider;
use Chronhub\Chronicler\Support\Contracts\TransactionalChronicler;
use Generator;
use Illuminate\Support\Collection;
use Throwable;
use function array_merge;

final class InMemoryTransactionalChronicler extends AbstractInMemoryChronicler implements TransactionalChronicler
{
    private bool $inTransaction = false;
    private array $cachedEvents = [];
    private Collection $cachedStreams;

    public function __construct(EventStreamProvider $eventStreamProvider)
    {
        parent::__construct($eventStreamProvider);

        $this->cachedStreams = new Collection();
    }

    public function persistFirstCommit(Stream $stream): void
    {
        $streamName = $stream->name();

        $category = $this->detectStreamCategory($streamName->toString());

        if ($this->hasStreamInCache($streamName)) {
            throw new StreamAlreadyExists(
                "Stream $streamName already exists but it has never been committed"
            );
        }

        if (!$this->eventStreamProvider->createStream($streamName->toString(), '', $category)) {
            throw StreamAlreadyExists::withStreamName($streamName);
        }

        $this->storeStreamEvents($streamName->toString(), $stream->events());
    }

    public function persist(Stream $stream): void
    {
        $streamName = $stream->name();

        if (!$this->hasStream($streamName) && !$this->hasStreamInCache($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }

        $this->storeStreamEvents($streamName->toString(), $stream->events());
    }

    public function pullCachedRecordedEvents(): array
    {
        $cachedEvents = $this->cachedEvents;

        $this->cachedEvents = [];

        return $cachedEvents;
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction) {
            throw new TransactionAlreadyStarted();
        }

        $this->inTransaction = true;
    }

    public function commitTransaction(): void
    {
        if (!$this->inTransaction) {
            throw new TransactionNotStarted();
        }

        $this->cachedStreams->each(function (array $streamEvents, string $streamName): void {
            $events = $this->decorateEventWithInternalPosition($streamEvents);

            $this->cachedEvents = array_merge($this->cachedEvents, $events);

            $stream = [$streamName => $events];

            $this->streams = $this->streams->mergeRecursive($stream);
        });

        $this->cachedStreams = new Collection();

        $this->inTransaction = false;
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction) {
            throw new TransactionNotStarted();
        }

        $this->cachedStreams = new Collection();

        $this->inTransaction = false;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function transactional(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);

            $this->commitTransaction();
        } catch (Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }

        return $result ?: true;
    }

    private function storeStreamEvents(string $streamName, Generator $events): void
    {
        if ($this->inTransaction) {
            $events = $this->decorateEventWithInternalPosition(iterator_to_array($events));

            $stream = [$streamName => $events];

            $this->cachedStreams = $this->cachedStreams->mergeRecursive($stream);
        }
    }

    private function hasStreamInCache(StreamName $streamName): bool
    {
        return $this->cachedStreams->has($streamName->toString());
    }
}
