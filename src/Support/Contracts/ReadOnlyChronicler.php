<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts;

use Generator;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

interface ReadOnlyChronicler
{
    /**
     * @return Generator<DomainEvent>
     */
    public function retrieveAll(StreamName $streamName,
                                AggregateId $aggregateId,
                                string $direction = 'asc'): Generator;

    /**
     * @return Generator<DomainEvent>
     */
    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator;

    /**
     * @param StreamName ...$streamNames
     *
     * @return StreamName[]
     */
    public function fetchStreamNames(StreamName ...$streamNames): array;

    /**
     * @param string ...$categoryNames
     *
     * @return string[]
     */
    public function fetchCategoryNames(string ...$categoryNames): array;

    public function hasStream(StreamName $streamName): bool;
}
