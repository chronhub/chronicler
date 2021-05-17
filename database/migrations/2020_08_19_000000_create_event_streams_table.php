<?php
declare(strict_types=1);

use Chronhub\Chronicler\Support\Contracts\Model\EventStreamModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventStreamsTable extends Migration
{
    public function up(): void
    {
        Schema::create(EventStreamModel::TABLE, static function (Blueprint $table) {
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
