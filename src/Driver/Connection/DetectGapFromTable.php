<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Driver\Connection;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use function count;

trait DetectGapFromTable
{
    /**
     * return array compose with
     *      key missing position + 1
     *      values with at least two members, the missing position - 1 , the position + 1
     *      and the missing position + 2 (could be absent if position + 1 is last insert).
     */
    public function detectGapsFromTable(string $table, int $from = 0, int $to = 0): array
    {
        $query = DB::table($table);

        if ($from > 0) {
            $query->where('no', '>=', $from);
        }

        if ($to > 0) {
            if ($to <= $from) {
                throw new InvalidArgumentException('To position must be greater than From position');
            }

            $query = $query->where('no', '<=', $to);
        }

        $query = $query->orderBy('no');

        $gaps = new Collection();
        $next = 0;

        foreach ($query->cursor() as $event) {
            $current = $event->no;

            if (0 !== $next && $current !== $next) {
                $gaps->push($event->no);
            }

            $next = $current + 1;
        }

        $gaps = $gaps
            ->map(fn (int $position): array => [$position - 2, $position, $position + 1])
            ->toArray();

        if (0 === count($gaps)) {
            return [];
        }

        $rows = [];

        foreach ($gaps as $gap) {
            $rows[$gap[1]] = DB::table($table)->whereIn('no', $gap)->get()->toArray();
        }

        return $rows;
    }
}
