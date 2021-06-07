<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Aggregate;

use Generator;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;

interface AggregateRootWithSnapshotting extends AggregateRoot
{
    /**
     * @return static|null
     */
    public function reconstituteFromSnapshotEvents(Generator $events): ?AggregateRootWithSnapshotting;
}
