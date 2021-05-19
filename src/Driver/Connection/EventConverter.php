<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection;

use stdClass;
use Chronhub\Foundation\Message\Message;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Chronicler\Support\Contracts\Support\JsonEncoder;
use Chronhub\Foundation\Support\Contracts\Message\MessageSerializer;

class EventConverter
{
    public function __construct(private MessageSerializer $eventSerializer,
                                private JsonEncoder $jsonEncoder)
    {
    }

    public function toArray(DomainEvent $event, bool $autoIncrement): array
    {
        $data = $this->eventSerializer->serializeMessage(new Message($event));

        $serializedEvent = [
            'event_id'   => (string) $data['headers'][Header::EVENT_ID],
            'event_type' => $data['headers'][Header::EVENT_TYPE],
            'content'    => $this->jsonEncoder->encode($data['content']),
            'headers'    => $this->jsonEncoder->encode($data['headers']),
            'created_at' => (string) $data['headers'][Header::EVENT_TIME],
        ];

        if ( ! $autoIncrement) {
            $serializedEvent['no'] = $data['headers'][Header::AGGREGATE_VERSION];
        }

        return $serializedEvent;
    }

    public function toDomainEvent(stdClass $event): DomainEvent
    {
        $data = [
            'content' => $this->jsonEncoder->decode($event->content),
            'headers' => $this->jsonEncoder->decode($event->headers),
            'no'      => $event->no,
        ];

        return $this->eventSerializer->unserializeContent($data)->current();
    }
}
