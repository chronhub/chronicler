<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Loader;

use Chronhub\Chronicler\Driver\Connection\EventConverter;
use Chronhub\Chronicler\Stream\StreamName;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Enumerable;

final class LazyQueryLoader extends StreamEventLoader
{
    public function __construct(protected EventConverter $eventConverter,
                                protected int $chunkSize = 5000)
    {
        //
    }

    protected function fromCollection(Builder $builder, StreamName $StreamName): Enumerable
    {
       return $builder->lazy($this->chunkSize);
    }
}
