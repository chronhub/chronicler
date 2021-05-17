<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Double;

use Chronhub\Foundation\Aggregate\HasAggregateRoot;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;

class SomeAggregateRoot implements AggregateRoot
{
    use HasAggregateRoot;

    public static function create(AggregateId $aggregateId, array $events): self
    {
        $aggregateRoot = new static($aggregateId);

        foreach ($events as $event) {
            $aggregateRoot->recordThat($event);
        }

        return $aggregateRoot;
    }

    public function countEventsToRelease(): int
    {
        return count($this->recordedEvents);
    }

    protected function apply(DomainEvent $event): void
    {
        ++$this->version;
    }
}
