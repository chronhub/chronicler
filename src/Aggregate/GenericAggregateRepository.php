<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Aggregate;

use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Support\Contracts\StreamProducer;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateType;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateCache;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateRepository;

final class GenericAggregateRepository implements AggregateRepository
{
    use HasReconstituteAggregate;
    use InteractWithAggregateRepository;

    public function __construct(protected AggregateType $aggregateType,
                                protected Chronicler $chronicler,
                                protected StreamProducer $streamProducer,
                                protected AggregateCache $aggregateCache,
                                protected AggregateEventReleaser $eventsReleaser)
    {
    }
}
