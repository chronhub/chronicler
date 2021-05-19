<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Exception;

use Chronhub\Chronicler\Support\Contracts\ChroniclingException;

class RuntimeException extends \Chronhub\Foundation\Exception\RuntimeException implements ChroniclingException
{
}
