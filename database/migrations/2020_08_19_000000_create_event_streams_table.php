<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Chronhub\Chronicler\Support\Contracts\Model\EventStreamModel;

class CreateEventStreamsTable extends Migration
{
    public function up(): void
    {
        Schema::create(EventStreamModel::TABLE, static function (Blueprint $table): void {
            $table->bigInteger('id', true);
            $table->string('real_stream_name', 150)->unique();
            $table->char('stream_name', 41);
            $table->string('category', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(EventStreamModel::TABLE);
    }
}
