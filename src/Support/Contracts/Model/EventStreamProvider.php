<?php

namespace Chronhub\Chronicler\Support\Contracts\Model;

interface EventStreamProvider
{
    /**
     * Create new stream
     *
     * @param string      $streamName
     * @param string      $tableName
     * @param string|null $category
     * @return bool
     */
    public function createStream(string $streamName, string $tableName, ?string $category = null): bool;

    /**
     * Delete stream
     *
     * @param string $streamName
     * @return bool
     */
    public function deleteStream(string $streamName): bool;

    /**
     * Filter by stream names
     *
     * @param string[] $streamNames
     * @return string[]
     */
    public function filterByStreams(array $streamNames): array;

    /**
     * @param array $categoryNames
     * @return string[]
     */
    public function filterByCategories(array $categoryNames): array;

    /**
     * Filter streams without internal
     * usually start with dollar sign $
     *
     * @return array
     */
    public function allStreamWithoutInternal(): array;

    /**
     * Check existence of stream name
     *
     * @param string $streamName
     * @return bool
     */
    public function hasRealStreamName(string $streamName): bool;
}
