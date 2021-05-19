<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Aggregate;

use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Chronicler\Support\Contracts\StreamProducer;
use Chronhub\Chronicler\Support\Contracts\ReadOnlyChronicler;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateType;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateCache;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateRepository;
use function reset;

final class GenericAggregateRepository implements AggregateRepository
{
    use HasReconstituteAggregate;

    public function __construct(protected AggregateType $aggregateType,
                                protected Chronicler $chronicler,
                                protected StreamProducer $streamProducer,
                                protected AggregateCache $aggregateCache,
                                protected AggregateEventReleaser $eventsReleaser)
    {
    }

    public function retrieve(AggregateId $aggregateId): ?AggregateRoot
    {
        if ($this->aggregateCache->has($aggregateId)) {
            return $this->aggregateCache->get($aggregateId);
        }

        $aggregateRoot = $this->reconstituteAggregateRoot($aggregateId);

        if ($aggregateRoot) {
            $this->aggregateCache->put($aggregateRoot);
        }

        return $aggregateRoot;
    }

    public function persist(AggregateRoot $aggregateRoot): void
    {
        $this->aggregateType->assertAggregateRootIsSupported($aggregateRoot::class);

        $events = $this->eventsReleaser->releaseEvents($aggregateRoot);

        if ( ! $firstEvent = reset($events)) {
            return;
        }

        $stream = $this->streamProducer->produceStream($aggregateRoot->aggregateId(), $events);

        $this->streamProducer->isFirstCommit($firstEvent)
            ? $this->chronicler->persistFirstCommit($stream)
            : $this->chronicler->persist($stream);

        $this->aggregateCache->forget($aggregateRoot->aggregateId());
    }

    public function chronicler(): ReadOnlyChronicler
    {
        return $this->chronicler;
    }

    public function flushCache(): void
    {
        $this->aggregateCache->flush();
    }
}
