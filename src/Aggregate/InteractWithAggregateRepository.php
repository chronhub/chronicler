<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Aggregate;

use Chronhub\Chronicler\Support\Contracts\Query\QueryFilter;
use Chronhub\Chronicler\Support\Contracts\ReadOnlyChronicler;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use function reset;

trait InteractWithAggregateRepository
{
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

    public function retrievePartially(AggregateId $aggregateId, QueryFilter $queryFilter): ?AggregateRoot
    {
        return $this->reconstituteAggregateRoot($aggregateId, $queryFilter);
    }

    public function persist(AggregateRoot $aggregateRoot): void
    {
        $this->aggregateType->assertAggregateRootIsSupported($aggregateRoot::class);

        $events = $this->eventsReleaser->releaseEvents($aggregateRoot);

        if (!$firstEvent = reset($events)) {
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
