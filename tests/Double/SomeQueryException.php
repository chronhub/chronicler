<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Double;

use Throwable;
use Illuminate\Database\QueryException;

final class SomeQueryException extends QueryException
{
    public function __construct(Throwable $previousException)
    {
        parent::__construct('some sql', [], $previousException);

        if (0 === $previousException->getCode()) {
            $this->code = '00000';
        }
    }
}
