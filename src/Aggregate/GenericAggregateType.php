<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Aggregate;

use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateType;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use function in_array;
use function class_exists;
use function is_subclass_of;

class GenericAggregateType implements AggregateType
{
    public function __construct(protected string $aggregateRootClassName,
                                protected array $map = [])
    {
        if ( ! class_exists($aggregateRootClassName)) {
            throw new InvalidArgumentException('Aggregate root must be a fqcn');
        }

        foreach ($map as $className) {
            if ( ! is_subclass_of($className, $this->aggregateRootClassName)) {
                throw new InvalidArgumentException("Class $className must inherit from $aggregateRootClassName");
            }
        }
    }

    public function aggregateRootClassName(): string
    {
        return $this->aggregateRootClassName;
    }

    public function determineFromEvent(DomainEvent $event): string
    {
        $aggregateType = $event->header(Header::AGGREGATE_TYPE);

        $this->assertAggregateRootIsSupported($aggregateType);

        return $aggregateType;
    }

    public function determineFromAggregateRoot(AggregateRoot $aggregateRoot): string
    {
        $this->assertAggregateRootIsSupported($aggregateRoot::class);

        return $aggregateRoot::class;
    }

    public function assertAggregateRootIsSupported(string $aggregateRoot): void
    {
        if ( ! $this->supportAggregateRoot($aggregateRoot)) {
            throw new InvalidArgumentException("Aggregate root $aggregateRoot class is not supported");
        }
    }

    private function supportAggregateRoot(string $aggregateRoot): bool
    {
        if ($aggregateRoot === $this->aggregateRootClassName) {
            return true;
        }

        return in_array($aggregateRoot, $this->map, true);
    }
}
