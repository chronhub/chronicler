<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Model;

interface EventStreamProvider
{
    /**
     * Create new stream.
     */
    public function createStream(string $streamName, string $tableName, ?string $category = null): bool;

    /**
     * Delete stream.
     */
    public function deleteStream(string $streamName): bool;

    /**
     * Filter by stream names.
     *
     * @param string[] $streamNames
     *
     * @return string[]
     */
    public function filterByStreams(array $streamNames): array;

    /**
     * @return string[]
     */
    public function filterByCategories(array $categoryNames): array;

    /**
     * Filter streams without internal
     * start with dollar sign $.
     */
    public function allStreamWithoutInternal(): array;

    /**
     * Check existence of stream.
     */
    public function hasRealStreamName(string $streamName): bool;
}
