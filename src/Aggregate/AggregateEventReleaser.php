<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Aggregate;

use Chronhub\Foundation\Message\Message;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Chronhub\Foundation\Support\Contracts\Message\MessageDecorator;
use function count;

class AggregateEventReleaser
{
    public function __construct(private MessageDecorator $messageDecorator)
    {
    }

    /**
     * @return array<DomainEvent>
     */
    public function releaseEvents(AggregateRoot $aggregateRoot): array
    {
        $events = $aggregateRoot->releaseEvents();

        if (0 === count($events)) {
            return [];
        }

        $version = $aggregateRoot->version() - count($events);
        $aggregateId = $aggregateRoot->aggregateId();

        $headers = [
            Header::AGGREGATE_ID      => $aggregateId->toString(),
            Header::AGGREGATE_ID_TYPE => $aggregateId::class,
            Header::AGGREGATE_TYPE    => $aggregateRoot::class,
        ];

        return array_map(
            function (DomainEvent $event) use ($headers, &$version) {
                return $this->messageDecorator->decorate(
                    new Message($event, $headers + [Header::AGGREGATE_VERSION => ++$version])
                )->event();
            }, $events);
    }
}
