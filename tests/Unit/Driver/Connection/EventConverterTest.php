<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Driver\Connection;

use stdclass;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Foundation\Message\Message;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Chronhub\Chronicler\Tests\Double\SomeDomainEvent;
use Chronhub\Chronicler\Driver\Connection\EventConverter;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Chronicler\Support\Contracts\Support\JsonEncoder;
use Chronhub\Foundation\Support\Contracts\Message\MessageSerializer;

/** @coversDefaultClass \Chronhub\Chronicler\Driver\Connection\EventConverter */
final class EventConverterTest extends TestCaseWithProphecy
{
    private JsonEncoder|ObjectProphecy $json;
    private MessageSerializer|ObjectProphecy $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->json = $this->prophesize(JsonEncoder::class);
        $this->serializer = $this->prophesize(MessageSerializer::class);
    }

    /**
     * @test
     */
    public function it_convert_message_event_to_array_with_auto_increment(): void
    {
        $event =   SomeDomainEvent::fromContent([]);

        $data = [
            Header::EVENT_ID => '1234',
            Header::EVENT_TYPE => 'event_type',
            'content' => 'content',
            'headers' => [
                Header::EVENT_ID => '1234',
                Header::EVENT_TYPE => 'event_type',
                Header::EVENT_TIME => 'date_time',
            ],
            'created_at' => 'date_time',
        ];

        $serialized = [
            'content' => 'content',
            'headers' => 'headers',
            'created_at' => 'date_time',
            'event_id' => '1234',
            'event_type' => 'event_type',
        ];

        $this->serializer->serializeMessage(new Message($event))->willReturn($data)->shouldBeCalled();

        $this->json->encode($data['content'])->willReturn('content')->shouldBeCalled();
        $this->json->encode($data['headers'])->willReturn('headers')->shouldBeCalled();

        $converter = new EventConverter($this->serializer->reveal(), $this->json->reveal());

        $this->assertEquals($serialized, $converter->toArray($event, true));
    }

    /**
     * @test
     */
    public function it_convert_message_event_to_array_without_auto_increment(): void
    {
        $event = SomeDomainEvent::fromContent([]);

        $data = [
            Header::EVENT_ID => '1234',
            Header::EVENT_TYPE => 'event_type',
            'content' => 'content',
            'headers' => [
                Header::EVENT_ID => '1234',
                Header::EVENT_TYPE => 'event_type',
                Header::EVENT_TIME => 'date_time',
                Header::AGGREGATE_VERSION => 12,
            ],
            'created_at' => 'date_time',
        ];

        $serialized = [
            'content' => 'content',
            'headers' => 'headers',
            'created_at' => 'date_time',
            'event_id' => '1234',
            'event_type' => 'event_type',
            'no' => 12,
        ];

        $this->serializer->serializeMessage(new Message($event))->willReturn($data)->shouldBeCalled();

        $this->json->encode($data['content'])->willReturn('content')->shouldBeCalled();
        $this->json->encode($data['headers'])->willReturn('headers')->shouldBeCalled();

        $converter = new EventConverter($this->serializer->reveal(), $this->json->reveal());

        $this->assertEquals($serialized, $converter->toArray($event, false));
    }

    /**
     * @test
     */
    public function it_convert_stdclass_to_message_instance(): void
    {
        $event = new stdclass();
        $event->no = 2;
        $event->content = 'content';
        $event->headers = 'headers';

        $data = [
            'content' => 'content',
            'headers' => 'headers',
            'no' => 2,
        ];

        $this->json->decode($event->content)->willReturn('content')->shouldBeCalled();
        $this->json->decode($event->headers)->willReturn('headers')->shouldBeCalled();

        $this->serializer->unserializeContent($data)->willYield([SomeDomainEvent::fromContent([])]);

        $converter = new EventConverter($this->serializer->reveal(), $this->json->reveal());

        $this->assertEquals(SomeDomainEvent::fromContent([]), $converter->toDomainEvent($event));
    }
}
