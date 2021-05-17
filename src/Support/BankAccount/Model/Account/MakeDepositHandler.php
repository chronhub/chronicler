<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerCollection;
use RuntimeException;

final class MakeDepositHandler
{
    public function __construct(private CustomerCollection $customerCollection,
                                private AccountCollection $accountCollection)
    {
        //
    }

    public function command(MakeDeposit $command): void
    {
        $customerId = $command->customerId();

        if (!$customer = $this->customerCollection->get($customerId)) {
            throw new RuntimeException('Customer not found');
        }

        $accountId = $command->accountId();

        if (!$account = $this->accountCollection->get($accountId)) {
            throw new RuntimeException('Account not found for customer');
        }

        $customer->deposit($account, $command->deposit());

        $this->accountCollection->store($account);
    }
}
