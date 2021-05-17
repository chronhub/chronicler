<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Stream;

use Generator;
use Illuminate\Support\LazyCollection;
use Iterator;

final class Stream
{
    private LazyCollection $events;

    public function __construct(private StreamName $streamName, iterable $events = [])
    {
        $this->events = $events instanceof LazyCollection
            ? $events
            : new LazyCollection($events);
    }

    public function name(): StreamName
    {
        return $this->streamName;
    }

    public function events(): Generator
    {
        yield from $this->events;

        return $this->events->count();
    }

    public function iterator(): LazyCollection
    {
        return $this->events;
    }
}
