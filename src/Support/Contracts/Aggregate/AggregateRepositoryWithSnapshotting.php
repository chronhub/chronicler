<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Aggregate;

use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

interface AggregateRepositoryWithSnapshotting extends AggregateRepository
{
    public function retrieveFromSnapshotStore(AggregateId $aggregateId): ?AggregateRootWithSnapshotting;
}
