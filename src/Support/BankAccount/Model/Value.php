<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model;

interface Value
{
    public function sameValueAs(Value $aValue): bool;
}
