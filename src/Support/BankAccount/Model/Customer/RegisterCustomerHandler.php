<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Customer;

use RuntimeException;

final class RegisterCustomerHandler
{
    public function __construct(private CustomerCollection $customerCollection)
    {
    }

    public function command(RegisterCustomer $command): void
    {
        $customerId = $command->customerId();

        if ($this->customerCollection->get($customerId)) {
            throw new RuntimeException('Customer already exists');
        }

        $customer = Customer::register($customerId, $command->customerName());

        $this->customerCollection->store($customer);
    }
}
