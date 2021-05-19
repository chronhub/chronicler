<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Query;

interface QueryFilter
{
    public function filterQuery(): callable;
}
