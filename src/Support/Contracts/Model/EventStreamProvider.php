<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Model;

interface EventStreamProvider
{
    public function createStream(string $streamName, string $tableName, ?string $category = null): bool;

    public function deleteStream(string $streamName): bool;

    /**
     * @param string[] $streamNames
     *
     * @return string[]
     */
    public function filterByStreams(array $streamNames): array;

    /**
     * @param string[] $categoryNames
     *
     * @return string[]
     */
    public function filterByCategories(array $categoryNames): array;

    /**
     * Filter streams without internal streams which
     * start with dollar sign $.
     *
     * @return string[]
     */
    public function allStreamWithoutInternal(): array;

    public function hasRealStreamName(string $streamName): bool;
}
