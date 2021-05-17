<?php

namespace Chronhub\Chronicler\Support\Contracts\Query;

interface QueryFilter
{
    /**
     * @return callable
     */
    public function filterQuery(): callable;
}
