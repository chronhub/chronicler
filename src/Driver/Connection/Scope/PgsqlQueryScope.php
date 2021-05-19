<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Scope;

use Chronhub\Chronicler\Driver\Connection\ConnectionQueryScope;
use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Illuminate\Database\Query\Builder;

class PgsqlQueryScope extends ConnectionQueryScope
{
    public function matchAggregateGreaterThanVersion(string $aggregateId,
                                                     string $aggregateType,
                                                     int $aggregateVersion): QueryFilter
    {
        // checkMe
        // allowing zero can include first version
        if ($aggregateVersion < 0) {
            throw new InvalidArgumentException("Aggregate version must be greater or equals than 0, current is $aggregateVersion");
        }

        $callback = function (Builder $query) use ($aggregateId, $aggregateType, $aggregateVersion): void {
            $query
                ->whereJsonContains('headers->__aggregate_id', $aggregateId)
                ->whereJsonContains('headers->__aggregate_type', $aggregateType)
                ->whereRaw('CAST(headers->>\'__aggregate_version\' AS INT) > ' . $aggregateVersion)
                ->orderByRaw('CAST(headers->>\'__aggregate_version\' AS INT)');
        };

        return $this->wrap($callback);
    }
}
