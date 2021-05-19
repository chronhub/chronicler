<?php

namespace Chronhub\Chronicler\Support\Contracts\Query;

interface QueryScope
{
    /**
     * @param int    $from
     * @param int    $to
     * @param string $direction asc|desc
     * @return QueryFilter
     */
    public function fromToPosition(int $from, int $to, string $direction = 'asc'): QueryFilter;

    /**
     * @param string $aggregateId
     * @param string $aggregateType
     * @param int    $aggregateVersion
     * @return QueryFilter
     */
    public function matchAggregateGreaterThanVersion(string $aggregateId,
                                                     string $aggregateType,
                                                     int $aggregateVersion): QueryFilter;
}
