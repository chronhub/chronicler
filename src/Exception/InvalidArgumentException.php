<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Exception;

use Chronhub\Chronicler\Support\Contracts\ChroniclingException;

class InvalidArgumentException extends \Chronhub\Foundation\Exception\InvalidArgumentException implements ChroniclingException
{
    //
}
