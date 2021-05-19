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

    private CustomerId $customerId;
    private int $balance = 0;

    public static function register(AccountId $accountId, CustomerId $customerId): self
    {
        $account = new static($accountId);

        $account->recordThat(AccountRegistered::forUser($accountId, $customerId));

        return $account;
    }

    public function makeDeposit(int $deposit): void
    {
        $this->recordThat(DepositMade::forUser($this->accountId(), $this->customerId, $deposit));
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

    protected function applyAccountRegistered(AccountRegistered $event): void
    {
        $this->customerId = $event->customerId();
    }

    protected function applyDepositMade(DepositMade $event): void
    {
        $this->balance += $event->deposit();
    }
}
