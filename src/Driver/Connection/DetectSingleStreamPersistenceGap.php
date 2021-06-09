<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection;

use stdClass;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use function array_values;

trait DetectSingleStreamPersistenceGap
{
    /**
     * Positions around missing position can not belong to the same identity
     */
    public function detectGapsFromTable(string $table, int $from = 0, int $to = 0): Collection
    {
        $query = DB::table($table, 't1')->selectRaw('*, no + 1');

        if ($from > 0) {
            $query->where('no', '>=', $from);
        }

        if ($to > 0) {
            if ($to <= $from) {
                throw new InvalidArgumentException('To position must be greater than From position');
            }

            $query = $query->where('no', '<=', $to);
        }

        return $query
            ->whereNotExists(function (Builder $q) use ($table): void {
                $q
                    ->selectRaw('NULL')
                    ->from($table, 't2')
                    ->whereRaw('t2.no = t1.no + 1');
            })
            ->orderBy('no')
            ->get()
            ->map(fn (stdClass $res): array => [$res->no + 1 => [$res->no, $res->no + 2, $res->no + 3]])
            ->slice(0, -1); // remove last id which never exists
    }

    /**
     * Find events according to the gap position.
     *
     * return collection with missing position key
     * and values with at least one member:
     *      the missing position - 1, could be absent if is a gap too
     *      the position + 1
     *      the missing position + 2, could be absent if position + 1 is last insert
     */
    public function lookUp(string $table, Collection $gaps): Collection
    {
        return $gaps->mapWithKeys(function (array $gaps) use ($table): array {
            foreach ($gaps as $missingPosition => $positions) {
                $result = DB::table($table)
                    ->whereIn('no', array_values($gaps)[0])
                    ->get()
                    ->groupBy(fn (stdClass $res) => $res->no)
                    ->transform(fn ($r) => $r[0]);

                return [$missingPosition => $result->toArray()];
            }
        });
    }
}
