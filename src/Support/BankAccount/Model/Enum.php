<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model;

use Serializable;
use MabeEnum\EnumSerializableTrait;

abstract class Enum extends \MabeEnum\Enum implements Value, Serializable
{
    use EnumSerializableTrait;

    public function sameValueAs(Value $aValue): bool
    {
        return $this->is($aValue);
    }

    public function toString(): string
    {
        return $this->getName();
    }
}
