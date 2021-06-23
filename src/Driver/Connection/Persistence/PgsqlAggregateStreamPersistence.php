<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Persistence;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Chronicler\Driver\Connection\EventConverter;
use Chronhub\Chronicler\Support\Contracts\StreamPersistence;
use function sha1;

final class PgsqlAggregateStreamPersistence implements StreamPersistence
{
    public function __construct(private EventConverter $eventConverter)
    {
    }

    public function tableName(StreamName $streamName): string
    {
        return '_' . sha1($streamName->toString());
    }

    public function up(string $tableName): ?callable
    {
        Schema::create($tableName, function (Blueprint $table): void {
            $table->id('no');
            $table->uuid('event_id')->unique();
            $table->string('event_type');
            $table->json('content');
            $table->jsonb('headers');
            $table->uuid('aggregate_id');
            $table->string('aggregate_type');
            $table->bigInteger('aggregate_version')->unique();
            $table->timestampTz('created_at', 6);
        });

        return null;
    }

    public function serializeMessage(DomainEvent $event): array
    {
        return $this->eventConverter->toArray($event, false);
    }

    public function isOneStreamPerAggregate(): bool
    {
        return true;
    }
}
