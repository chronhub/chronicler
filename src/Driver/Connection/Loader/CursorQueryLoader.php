<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Loader;

use Chronhub\Chronicler\Driver\Connection\EventConverter;
use Chronhub\Chronicler\Stream\StreamName;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

final class CursorQueryLoader extends StreamEventLoader
{
    public function __construct(protected EventConverter $eventConverter)
    {
        //
    }

    protected function generateFrom(Builder $builder, StreamName $StreamName): LazyCollection|Collection
    {
        return $builder->cursor();
    }
}
