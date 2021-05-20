<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use RuntimeException;
use function abs;

final class Balance
{
    private int $amount;
    private int $overdraft = 0;

    private function __construct(int $amount)
    {
        $this->amount = abs($amount);
    }

    public static function startAt(int $amount): self
    {
        return new self($amount);
    }

    public static function startAtZero(): self
    {
        return new self(0);
    }

    public function add(int $amount): void
    {
        $this->amount += abs($amount);
    }

    public function subtract(int $amount): void
    {
        if ($this->willOverflow($amount)) {
            throw new RuntimeException('Invalid withdraw operation');
        }

        $this->amount -= abs($amount);
    }

    public function available(): int
    {
        return $this->amount;
    }

    public function availableWithOverdraft(): int
    {
        return $this->amount + $this->overdraft;
    }

    public function willOverflow(int $amount): bool
    {
        return ($this->amount + $this->overdraft) - abs($amount) < 0;
    }
}
