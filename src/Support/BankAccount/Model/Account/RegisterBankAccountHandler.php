<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use RuntimeException;
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

        if ( ! $this->customerCollection->get($customerId)) {
            throw new RuntimeException('Customer not found');
        }

        $accountId = $command->accountId();

        if ($this->accountCollection->get($accountId)) {
            throw new RuntimeException('Account already exists for customer');
        }

        //attach account through customer

        $account = Account::register($accountId, $customerId);

        $this->accountCollection->store($account);
    }
}
