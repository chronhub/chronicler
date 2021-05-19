<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection;

use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Chronicler\Support\Contracts\Query\QueryScope;
use Illuminate\Database\Query\Builder;

abstract class ConnectionQueryScope implements QueryScope
{
    public function fromToPosition(int $from, int $to, string $direction = 'asc'): QueryFilter
    {
        if ($from < 1) {
            throw new InvalidArgumentException("From position must be greater than 0");
        }

        if ($to <= $from) {
            throw new InvalidArgumentException("To position must be greater than from position");
        }

        $callback = function (Builder $builder) use ($from, $to, $direction): void {
            $builder->whereBetween('no', [$from, $to]);
            $builder->orderBy('no', $direction);
        };

        return $this->wrap($callback);
    }

    protected function wrap(callable $query): QueryFilter
    {
        return new class($query) implements QueryFilter {
            /**
             * @var callable
             */
            private $query;

            public function __construct($query)
            {
                $this->query = $query;
            }

            public function filterQuery(): callable
            {
                return $this->query;
            }
        };
    }
}
