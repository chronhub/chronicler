<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Loader;

use Illuminate\Support\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\LazyCollection;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Driver\Connection\EventConverter;
use function min;
use function is_int;

final class LazyQueryLoader extends StreamEventLoader
{
    public function __construct(protected EventConverter $eventConverter,
                                protected int $chunkSize = 5000)
    {
    }

    protected function generateFrom(Builder $builder, StreamName $StreamName): LazyCollection|Collection
    {
        // When using the built-in lazy function on the query builder,
        // limiting the number of results does not work
        // Note that it's still chunk the query if the limit is greater
        // than the chunk size property

        $limit = is_int($builder->limit) ? $builder->limit : null;

        $query = $builder->lazy(min($limit ?? PHP_INT_MAX, $this->chunkSize));

        if ($limit) {
            $query = $query->take($limit);
        }

        return $query;
    }
}
