<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Tracking;

use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Tracker\TrackerContext;
use Chronhub\Foundation\Support\Contracts\Message\MessageDecorator;

interface ContextualStream extends TrackerContext
{
    public function withStream(Stream $stream): void;

    public function withStreamName(StreamName $streamName): void;

    /**
     * @param StreamName ...$streamNames
     */
    public function withStreamNames(StreamName ...$streamNames): void;

    /**
     * @param string ...$categoryNames
     */
    public function withCategoryNames(string ...$categoryNames): void;

    public function setStreamExists(bool $isStreamExists): void;

    public function withAggregateId(AggregateId $aggregateId): void;

    public function withQueryFilter(QueryFilter $queryFilter): void;

    public function withDirection(string $direction): void;

    public function decorateStreamEvents(MessageDecorator $messageDecorator): void;

    public function stream(): ?Stream;

    public function streamName(): ?StreamName;

    /**
     * @return StreamName[]
     */
    public function streamNames(): array;

    /**
     * @return string[]
     */
    public function categoryNames(): array;

    public function aggregateId(): ?AggregateId;

    public function direction(): ?string;

    public function queryFilter(): ?QueryFilter;

    public function isStreamExists(): bool;

    public function hasStreamNotFound(): bool;

    public function hasStreamAlreadyExits(): bool;

    public function hasRaceCondition(): bool;
}
