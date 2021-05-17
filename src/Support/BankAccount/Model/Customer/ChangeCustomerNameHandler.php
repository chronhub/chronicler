<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Customer;

use RuntimeException;

final class ChangeCustomerNameHandler
{
    public function __construct(private CustomerCollection $customerCollection)
    {
        //
    }

    public function command(ChangeCustomerName $command): void
    {
        if (!$customer = $this->customerCollection->get($command->customerId())) {
            throw new RuntimeException("Customer not found");
        }

        $customer->changeName($command->customerName());

        $this->customerCollection->store($customer);
    }
}
