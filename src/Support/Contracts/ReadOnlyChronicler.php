<?php

namespace Chronhub\Chronicler\Support\Contracts;

use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Generator;

interface ReadOnlyChronicler
{
    /**
     * @param StreamName  $streamName
     * @param AggregateId $aggregateId
     * @param string      $direction
     * @return Generator<DomainEvent>
     */
    public function retrieveAll(StreamName $streamName,
                                AggregateId $aggregateId,
                                string $direction = 'asc'): Generator;

    /**
     * @param StreamName  $streamName
     * @param QueryFilter $queryFilter
     * @return Generator<DomainEvent>
     */
    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator;

    /**
     * @param StreamName ...$streamNames
     * @return StreamName[]
     */
    public function fetchStreamNames(StreamName ...$streamNames): array;

    /**
     * @param string ...$categoryNames
     * @return string[]
     */
    public function fetchCategoryNames(string ...$categoryNames): array;

    /**
     * @param StreamName $streamName
     * @return bool
     */
    public function hasStream(StreamName $streamName): bool;
}
