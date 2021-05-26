<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use RuntimeException;
use Chronhub\Foundation\Aggregate\HasAggregateRoot;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;

final class Account implements AggregateRoot
{
    use HasAggregateRoot;

    public const MAX_FAILURES_BEFORE_SUSPEND_ACCOUNT = 20;

    private Balance $balance;
    private int $failures = 0;
    private CustomerId $customerId;
    private AccountStatus $status;

    public static function register(AccountId $accountId, CustomerId $customerId): self
    {
        $account = new static($accountId);

        $account->recordThat(
            AccountRegistered::forUser(
                $accountId, $customerId, AccountStatus::ACTIVE(), Balance::startAtZero()
            )
        );

        return $account;
    }

    public function makeDeposit(int $deposit): void
    {
        $this->assertAccountIsActive();

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
        $this->assertAccountIsActive();

        if ($this->balance->willOverflow($withdraw)) {
            $this->recordThat(WithdrawFailed::forUser(
                $this->accountId(), $this->customerId, $withdraw, $this->balance, $this->failures)
            );

            if (self::MAX_FAILURES_BEFORE_SUSPEND_ACCOUNT === $this->failures + 1) {
                $this->recordThat(AccountSuspended::forUser(
                    $this->accountId(), $this->customerId, AccountStatus::SUSPENDED(), $this->status)
                );
            }
        } else {
            $this->recordThat(WithdrawMade::forUser(
                $this->accountId(), $this->customerId, $withdraw, $this->balance)
            );
        }
    }

    private function assertAccountIsActive(): void
    {
        if ( ! $this->status->sameValueAs(AccountStatus::ACTIVE())) {
            throw new RuntimeException('Account is ' . $this->status->getValue());
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

    public function status(): AccountStatus
    {
        return $this->status;
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
        $this->status = $event->accountStatus();
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

    protected function applyAccountSuspended(AccountSuspended $event): void
    {
        $this->status = $event->newStatus();
    }

    protected function applyAccountFailuresReset(AccountFailuresReset $event): void
    {
        $this->failures = $event->newFailures();
    }
}
