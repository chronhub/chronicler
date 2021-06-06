<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection;

use stdClass;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use function array_values;

trait DetectGapFromTable
{
    /**
     * Only for single stream strategy.
     *
     * return array compose with
     *      key missing position + 1
     *      values with at least two members, the missing position - 1 , the position + 1
     *      and the missing position + 2 (could be absent if position + 1 is last insert).
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

        $gaps = $query
            ->whereNotExists(function (Builder $q) use ($table): void {
                $q
                    ->selectRaw('NULL')
                    ->from($table, 't2')
                    ->whereRaw('t2.no = t1.no + 1');
            })
            ->orderBy('no')
            ->get()
            ->map(fn (stdClass $res): array => [$res->no + 1 => [$res->no, $res->no + 2, $res->no + 3]])
            ->slice(0, -1);

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
