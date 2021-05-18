<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Persistence;

use Illuminate\Support\Facades\DB;

final class PgsqlSingleStreamPersistence extends AbstractSingleStreamPersistence
{
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
                CONSTRAINT aggregate_version_not_null CHECK ((headers->>\'__aggregate_version\') IS NOT NULL),
                CONSTRAINT aggregate_type_not_null CHECK ((headers->>\'__aggregate_type\') IS NOT NULL),
                CONSTRAINT aggregate_id_not_null CHECK ((headers->>\'__aggregate_id\') IS NOT NULL),
                UNIQUE (event_id)
            );'
        );

        DB::statement('CREATE UNIQUE INDEX ON ' . $tableName . '((headers->>\'__aggregate_type\'), (headers->>\'__aggregate_id\'), (headers->>\'__aggregate_version\'));');

        DB::statement('CREATE INDEX ON ' . $tableName . '((headers->>\'__aggregate_type\'), (headers->>\'__aggregate_id\'), no);');

        return null;
    }
}
