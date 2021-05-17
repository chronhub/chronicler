<?php

namespace Chronhub\Chronicler\Support\BankAccount\Model;

interface Value
{
    public function sameValueAs(Value $aValue): bool;
}
