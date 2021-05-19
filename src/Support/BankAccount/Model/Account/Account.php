<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Foundation\Aggregate\HasAggregateRoot;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;

final class Account implements AggregateRoot
{
    use HasAggregateRoot;

    private int $balance = 0;
    private int $failures = 0;
    private CustomerId $customerId;

    public static function register(AccountId $accountId, CustomerId $customerId): self
    {
        $account = new static($accountId);

        $account->recordThat(AccountRegistered::forUser($accountId, $customerId));

        return $account;
    }

    public function makeDeposit(int $deposit): void
    {
        $this->recordThat(DepositMade::forUser(
            $this->accountId(), $this->customerId, $deposit, $this->balance)
        );
    }

    public function makeWithdraw(int $withdraw): void
    {
        $withdraw = abs($withdraw);

        if ($this->balance - $withdraw < 0) {
            $this->recordThat(WithdrawFailed::forUser(
                $this->accountId(), $this->customerId, $withdraw, $this->balance, $this->failures)
            );
        } else {
            $this->recordThat(WithdrawMade::forUser(
                $this->accountId(), $this->customerId, $withdraw, $this->balance)
            );
        }
    }

    public function accountId(): AccountId|AggregateId
    {
        return $this->aggregateId();
    }

    public function customerId(): CustomerId
    {
        return $this->customerId;
    }

    public function balance(): int
    {
        return $this->balance;
    }

    public function failures(): int
    {
        return $this->failures;
    }

    protected function applyAccountRegistered(AccountRegistered $event): void
    {
        $this->customerId = $event->customerId();
    }

    protected function applyDepositMade(DepositMade $event): void
    {
        $this->balance += $event->deposit();
    }

    protected function applyWithdrawMade(WithdrawMade $event): void
    {
        $this->balance -= $event->withdraw();
    }

    protected function applyWithdrawFailed(WithdrawFailed $event): void
    {
        ++$this->failures;
    }
}
