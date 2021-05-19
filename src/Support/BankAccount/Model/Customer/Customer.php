<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Customer;

use Chronhub\Foundation\Aggregate\HasAggregateRoot;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\Account;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;

final class Customer implements AggregateRoot
{
    use HasAggregateRoot;

    private CustomerName $name;

    public static function register(CustomerId $customerId, CustomerName $customerName): self
    {
        $customer = new static($customerId);

        $customer->recordThat(CustomerRegistered::withCustomer($customerId, $customerName));

        return $customer;
    }

    public function deposit(Account $account, int $deposit): void
    {
        $account->makeDeposit($deposit);
    }

    public function withdraw(Account $account, int $withdraw): void
    {
        $account->makeWithdraw($withdraw);
    }

    public function changeName(CustomerName $newName): void
    {
        if ($this->name->sameValueAs($newName)) {
            return;
        }

        $this->recordThat(CustomerNameChanged::forCustomer($this->customerId(), $newName, $this->name));
    }

    public function customerId(): CustomerId|AggregateId
    {
        return $this->aggregateId;
    }

    public function name(): CustomerName
    {
        return $this->name;
    }

    protected function applyCustomerRegistered(CustomerRegistered $event): void
    {
        $this->name = $event->customerName();
    }

    protected function applyCustomerNameChanged(CustomerNameChanged $event): void
    {
        $this->name = $event->newCustomerName();
    }
}
