<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Persistence;

use Chronhub\Chronicler\Driver\Connection\EventConverter;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Contracts\StreamPersistence;
use Chronhub\Foundation\Message\DomainEvent;
use Illuminate\Support\Facades\DB;

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
        DB::statement(
            'CREATE TABLE ' . $tableName . ' (
                no BIGSERIAL,
                event_id UUID NOT NULL,
                event_type VARCHAR(100) NOT NULL,
                content JSON NOT NULL,
                headers JSONB NOT NULL,
                created_at TIMESTAMP(6) NOT NULL,
                PRIMARY KEY (no),
                UNIQUE (event_id)
            );'
        );

        DB::statement(
            "CREATE UNIQUE INDEX on $tableName ((headers->>'__aggregate_version'));"
        );

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
