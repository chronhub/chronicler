<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Foundation\Aggregate\HasAggregateRoot;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Snapshot\Aggregate\HasReconstituteSnapshotAggregate;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateRootWithSnapshotting;

final class Account implements AggregateRootWithSnapshotting
{
    use HasAggregateRoot;
    use HasReconstituteSnapshotAggregate;

    private Balance $balance;
    private int $failures = 0;
    private CustomerId $customerId;

    public static function register(AccountId $accountId, CustomerId $customerId): self
    {
        $account = new static($accountId);

        $account->recordThat(
            AccountRegistered::forUser(
                $accountId, $customerId, Balance::startAtZero()
            )
        );

        return $account;
    }

    public function makeDeposit(int $deposit): void
    {
        $this->recordThat(DepositMade::forUser(
            $this->accountId(), $this->customerId, $deposit, $this->balance)
        );

        if (0 !== $this->failures) {
            $this->recordThat(AccountFailuresReset::forUser(
                $this->accountId(), $this->customerId, 0, $this->failures
            ));
        }
    }

    public function makeWithdraw(int $withdraw): void
    {
        if ($this->balance->willOverflow($withdraw)) {
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

    public function balance(): Balance
    {
        return clone $this->balance;
    }

    public function failures(): int
    {
        return $this->failures;
    }

    protected function applyAccountRegistered(AccountRegistered $event): void
    {
        $this->customerId = $event->customerId();
        $this->balance = $event->balance();
    }

    protected function applyDepositMade(DepositMade $event): void
    {
        $this->balance->add($event->deposit());
    }

    protected function applyWithdrawMade(WithdrawMade $event): void
    {
        $this->balance->subtract($event->withdraw());
    }

    protected function applyWithdrawFailed(WithdrawFailed $event): void
    {
        ++$this->failures;
    }

    protected function applyAccountFailuresReset(AccountFailuresReset $event): void
    {
        $this->failures = $event->newFailures();
    }
}
