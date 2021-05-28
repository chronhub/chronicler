<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Chronicler\Support\BankAccount\Exception\BankAccountException;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerCollection;

final class RegisterBankAccountHandler
{
    public function __construct(private AccountCollection $accountCollection,
                                private CustomerCollection $customerCollection)
    {
    }

    public function command(RegisterBankAccount $command): void
    {
        $customerId = $command->customerId();

        if ( ! $customer = $this->customerCollection->get($customerId)) {
            throw new BankAccountException('Customer not found');
        }

        $accountId = $command->accountId();

        if ($this->accountCollection->get($accountId)) {
            throw new BankAccountException('Account already exists for customer');
        }

        $account = $customer->attachAccount($accountId);

        $this->accountCollection->store($account);
    }
}
