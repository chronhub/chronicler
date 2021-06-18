<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection\Persistence;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

final class PgsqlSingleStreamPersistence extends AbstractSingleStreamPersistence
{
    public function up(string $tableName): ?callable
    {
        Schema::create($tableName, function (Blueprint $table): void {
            $table->id('no');
            $table->uuid('event_id');
            $table->string('event_type');
            $table->json('content');
            $table->jsonb('headers');
            $table->uuid('aggregate_id');
            $table->string('aggregate_type');
            $table->bigInteger('aggregate_version');
            $table->timestampTz('created_at', 6);
            $table->unique(['aggregate_type', 'aggregate_id', 'aggregate_version']);
        });

        return null;
    }
}
