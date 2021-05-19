<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\InMemory;

use Illuminate\Support\Collection;
use Chronhub\Chronicler\Support\Contracts\Model\EventStreamProvider;
use function is_null;
use function in_array;
use function is_string;

final class InMemoryEventStream implements EventStreamProvider
{
    private Collection $eventStreams;

    public function __construct()
    {
        $this->eventStreams = new Collection();
    }

    public function createStream(string $streamName, string $tableName, ?string $category = null): bool
    {
        if ( ! $this->hasEventStream($streamName)) {
            $this->eventStreams->put($streamName, $category);

            return true;
        }

        return false;
    }

    public function deleteStream(string $streamName): bool
    {
        if ( ! $this->hasEventStream($streamName)) {
            return false;
        }

        $this->eventStreams->forget($streamName);

        return true;
    }

    public function filterByStreams(array $streamNames): array
    {
        return $this->eventStreams->filter(
            fn (?string $category, string $streamName) => is_null($category) && in_array($streamName, $streamNames)
        )->keys()->toArray();
    }

    public function filterByCategories(array $categoryNames): array
    {
        return $this->eventStreams->filter(
            fn (?string $category) => is_string($category) && in_array($category, $categoryNames)
        )->keys()->toArray();
    }

    public function allStreamWithoutInternal(): array
    {
        return $this->eventStreams->filter(
            fn (?string $category, string $streamName) => is_null($category)
        )->keys()->toArray();
    }

    public function hasRealStreamName(string $streamName): bool
    {
        return $this->hasEventStream($streamName);
    }

    private function hasEventStream(string $streamName): bool
    {
        return $this->eventStreams->has($streamName);
    }
}
