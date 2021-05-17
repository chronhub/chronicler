<?php

namespace Chronhub\Chronicler\Support\Contracts\Tracking;

use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Message\MessageDecorator;
use Chronhub\Foundation\Support\Contracts\Tracker\TrackerContext;

interface ContextualStream extends TrackerContext
{
    /**
     * @param Stream $stream
     */
    public function withStream(Stream $stream): void;

    /**
     * @param StreamName $streamName
     */
    public function withStreamName(StreamName $streamName): void;

    /**
     * @param StreamName ...$streamNames
     */
    public function withStreamNames(StreamName ...$streamNames): void;

    /**
     * @param string ...$categoryNames
     */
    public function withCategoryNames(string ...$categoryNames): void;

    /**
     * @param bool $isStreamExists
     */
    public function setStreamExists(bool $isStreamExists): void;

    /**
     * @param AggregateId $aggregateId
     */
    public function withAggregateId(AggregateId $aggregateId): void;

    /**
     * @param QueryFilter $queryFilter
     */
    public function withQueryFilter(QueryFilter $queryFilter): void;

    /**
     * @param string $direction
     */
    public function withDirection(string $direction): void;

    /**
     * @param MessageDecorator $messageDecorator
     */
    public function decorateStreamEvents(MessageDecorator $messageDecorator): void;

    /**
     * @return Stream|null
     */
    public function stream(): ?Stream;

    /**
     * @return StreamName|null
     */
    public function streamName(): ?StreamName;

    /**
     * @return StreamName[]
     */
    public function streamNames(): array;

    /**
     * @return string[]
     */
    public function categoryNames(): array;

    /**
     * @return AggregateId|null
     */
    public function aggregateId(): ?AggregateId;

    /**
     * @return string|null
     */
    public function direction(): ?string;

    /**
     * @return QueryFilter|null
     */
    public function queryFilter(): ?QueryFilter;

    /**
     * @return bool
     */
    public function isStreamExists(): bool;

    /**
     * @return bool
     */
    public function hasStreamNotFound(): bool;

    /**
     * @return bool
     */
    public function hasStreamAlreadyExits(): bool;

    /**
     * @return bool
     */
    public function hasRaceCondition(): bool;
}
