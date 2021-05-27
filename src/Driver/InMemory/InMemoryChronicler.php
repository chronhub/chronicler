<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\InMemory;

use Generator;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Exception\StreamNotFound;
use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Chronicler\Exception\StreamAlreadyExists;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use function iterator_to_array;

final class InMemoryChronicler extends AbstractInMemoryChronicler
{
    private const TABLE_NAME = '';

    public function persistFirstCommit(Stream $stream): void
    {
        $streamName = $stream->name();

        $category = $this->detectStreamCategory($streamName->toString());

        if ( ! $this->eventStreamProvider->createStream($streamName->toString(), self::TABLE_NAME, $category)) {
            throw StreamAlreadyExists::withStreamName($streamName);
        }

        $this->storeStreamEvents($streamName->toString(), $stream->events());
    }

    public function persist(Stream $stream): void
    {
        $streamName = $stream->name();

        if ( ! $this->hasStream($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }

        $this->storeStreamEvents($streamName->toString(), $stream->events());
    }

    public function pullCachedRecordedEvents(): array
    {
        return $this->streams
            ->flatten()
            ->sortByDesc(fn (AggregateChanged $event) => $event->header(Header::EVENT_TIME))
            ->toArray();
    }

    private function storeStreamEvents(string $streamName, Generator $events): void
    {
        $events = $this->decorateEventWithInternalPosition(iterator_to_array($events));

        $this->streams = $this->streams->mergeRecursive([$streamName => $events]);
    }
}
