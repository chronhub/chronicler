<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Scope;

use Illuminate\Database\Query\Builder;
use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Chronicler\Driver\Connection\ConnectionQueryScope;

class PgsqlQueryScope extends ConnectionQueryScope
{
    public function matchAggregateGreaterThanVersion(string $aggregateId,
                                                     string $aggregateType,
                                                     int $aggregateVersion,
                                                     string $direction = 'asc'): QueryFilter
    {
        if ($aggregateVersion < 0) {
            throw new InvalidArgumentException("Aggregate version must be greater or equals than 0, current is $aggregateVersion");
        }

        $callback = function (Builder $query) use ($aggregateId, $aggregateType, $aggregateVersion, $direction): void {
            $query
                ->where('aggregate_id', $aggregateId)
                ->where('aggregate_type', $aggregateType)
                ->where('aggregate_version)', '>', $aggregateVersion)
                ->orderBy('no', $direction);
        };

        return $this->wrap($callback);
    }
}
