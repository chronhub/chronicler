<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Loader;

use Chronhub\Chronicler\Driver\Connection\EventConverter;
use Chronhub\Chronicler\Stream\StreamName;
use Generator;
use Illuminate\Database\Query\Builder;

final class GetQueryLoader extends StreamEventLoader
{
    public function __construct(protected EventConverter $eventConverter)
    {
        //
    }

    protected function generateFrom(Builder $builder, StreamName $StreamName): Generator
    {
        yield from $builder->get();
    }
}
