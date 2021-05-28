<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Customer;

use Chronhub\Chronicler\Support\BankAccount\Exception\BankAccountException;

final class ChangeCustomerNameHandler
{
    public function __construct(private CustomerCollection $customerCollection)
    {
    }

    public function command(ChangeCustomerName $command): void
    {
        if ( ! $customer = $this->customerCollection->get($command->customerId())) {
            throw new BankAccountException('Customer not found');
        }

        $customer->changeName($command->customerName());

        $this->customerCollection->store($customer);
    }
}
