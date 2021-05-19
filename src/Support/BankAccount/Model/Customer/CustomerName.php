<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Customer;

use Chronhub\Chronicler\Support\BankAccount\Model\Value;

final class CustomerName implements Value
{
    private function __construct(private string $name)
    {
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function sameValueAs(Value $aValue): bool
    {
        return $aValue instanceof $this && $this->toString() === $aValue->toString();
    }

    public function toString(): string
    {
        return $this->name;
    }
}
