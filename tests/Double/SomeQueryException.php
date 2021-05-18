<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Double;

use Illuminate\Database\QueryException;
use Throwable;

final class SomeQueryException extends QueryException
{
    public function __construct(Throwable $previousException)
    {
        parent::__construct('some sql', [], $previousException);

        if ($previousException->getCode() === 0) {
            $this->code = '00000';
        }
    }
}
