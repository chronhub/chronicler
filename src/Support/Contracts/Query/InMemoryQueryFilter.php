<?php

namespace Chronhub\Chronicler\Support\Contracts\Query;

interface InMemoryQueryFilter extends QueryFilter
{
    /**
     * Order by ascendant or descendant
     *
     * @return string
     */
    public function orderBy(): string;
}
