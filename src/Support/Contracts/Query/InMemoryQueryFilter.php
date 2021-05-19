<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Query;

interface InMemoryQueryFilter extends QueryFilter
{
    /**
     * Order by ascendant or descendant.
     */
    public function orderBy(): string;
}
