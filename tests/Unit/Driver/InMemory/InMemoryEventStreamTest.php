<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Driver\InMemory;

use Chronhub\Chronicler\Driver\InMemory\InMemoryEventStream;
use Chronhub\Chronicler\Tests\TestCase;

final class InMemoryEventStreamTest extends TestCase
{
    /**
     * @test
     *
     * @covers ::__construct
     * @covers ::hasRealStreamName
     */
    public function it_can_be_constructed(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('customer_stream'));
    }

    /**
     * @test
     */
    public function it_create_event_stream(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('customer_stream'));

        $created = $eventStream->createStream('customer_stream', '');

        $this->assertTrue($created);
        $this->assertTrue($eventStream->hasRealStreamName('customer_stream'));
    }

    /**
     * @test
     */
    public function it_return_false_when_stream_already_exists(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('customer_stream'));

        $created = $eventStream->createStream('customer_stream', '');

        $this->assertTrue($created);
        $this->assertTrue($eventStream->hasRealStreamName('customer_stream'));

        $this->assertFalse($eventStream->createStream('customer_stream', ''));
    }

    /**
     * @test
     */
    public function it_return_false_when_category_already_exists(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('customer-123'));

        $created = $eventStream->createStream('customer-123', '', 'customer');
        $this->assertTrue($created);

        $this->assertFalse($eventStream->createStream('customer-123', '', 'customer-123'));
    }

    /**
     * @test
     */
    public function it_create_event_stream_with_category(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('customer-123'));
        $this->assertEmpty($eventStream->filterByCategories(['customer-123']));

        $eventStream->createStream('customer-123', '', 'customer');

        $this->assertTrue($eventStream->hasRealStreamName('customer-123'));
        $this->assertEquals(['customer-123'], $eventStream->filterByCategories(['customer']));
    }

    /**
     * @test
     */
    public function it_delete_stream(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('customer_stream'));
        $this->assertEmpty($eventStream->filterByCategories(['customer-123']));

        $this->assertTrue($eventStream->createStream('customer_stream', ''));
        $this->assertTrue($eventStream->hasRealStreamName('customer_stream'));

        $this->assertTrue($eventStream->deleteStream('customer_stream'));

        $this->assertFalse($eventStream->hasRealStreamName('customer_stream'));
    }

    /**
     * @test
     */
    public function it_return_false_when_delete_stream_does_not_exists(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('customer_stream'));

        $this->assertFalse($eventStream->deleteStream('customer_stream'));
    }

    /**
     * @test
     */
    public function it_return_filtered_streams(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('customer_stream'));
        $this->assertFalse($eventStream->hasRealStreamName('order_stream'));

        $eventStream->createStream('customer_stream', '', 'customer-123');
        $eventStream->createStream('order_stream', '');

        $this->assertTrue($eventStream->hasRealStreamName('customer_stream'));
        $this->assertTrue($eventStream->hasRealStreamName('order_stream'));

        $this->assertEquals(['order_stream'], $eventStream->filterByStreams(['order_stream']));
    }

    /**
     * @test
     *
     * @covers ::allStreamWithoutInternal
     */
    public function it_return_all_streams_without_categories(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('customer_stream'));
        $this->assertFalse($eventStream->hasRealStreamName('order_stream'));

        $eventStream->createStream('customer_stream', '', 'customer-123');
        $eventStream->createStream('order_stream', '');

        $this->assertTrue($eventStream->hasRealStreamName('customer_stream'));
        $this->assertTrue($eventStream->hasRealStreamName('order_stream'));

        $this->assertEquals(['order_stream'], $eventStream->allStreamWithoutInternal());
    }
}
