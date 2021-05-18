<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection;

use Chronhub\Chronicler\Support\Contracts\Model\EventStreamModel;
use Chronhub\Chronicler\Support\Contracts\Model\EventStreamProvider;
use Illuminate\Database\Eloquent\Model;

final class EventStream extends Model implements EventStreamModel, EventStreamProvider
{
    public $timestamps = false;
    protected $table = self::TABLE;
    protected $fillable = ['stream_name', 'real_stream_name', 'category'];

    public function createStream(string $streamName, string $tableName, ?string $category = null): bool
    {
        return $this->newInstance([
            'real_stream_name' => $streamName,
            'stream_name'      => $tableName,
            'category'         => $category
        ])->save();
    }

    public function deleteStream(string $streamName): bool
    {
        $result = $this->newQuery()
            ->where('real_stream_name', $streamName)
            ->delete();

        return 1 === $result;
    }

    public function filterByStreams(array $streamNames): array
    {
        return $this->newQuery()
            ->whereIn('real_stream_name', $streamNames)
            ->get()
            ->pluck('real_stream_name')
            ->toArray();
    }

    public function filterByCategories(array $categoryNames): array
    {
        return $this->newQuery()
            ->whereIn('category', $categoryNames)
            ->get()
            ->pluck('real_stream_name')
            ->toArray();
    }

    public function allStreamWithoutInternal(): array
    {
        return $this->newQuery()
            ->whereRaw("real_stream_name NOT LIKE '$%'")
            ->pluck('real_stream_name')
            ->toArray();
    }

    public function hasRealStreamName(string $streamName): bool
    {
        return $this->newQuery()
            ->where('real_stream_name', $streamName)
            ->exists();
    }

    public function realStreamName(): string
    {
        return $this['real_stream_name'];
    }

    public function tableName(): string
    {
        return $this['stream_name'];
    }

    public function category(): ?string
    {
        return $this['category'];
    }
}
