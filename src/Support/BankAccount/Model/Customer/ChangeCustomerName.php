<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Customer;

use Chronhub\Foundation\Message\DomainCommand;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

final class ChangeCustomerName extends DomainCommand
{
    public static function withCustomer(string $customerId, string $newCustomerName): self
    {
        return new self(
            [
                'customer_id'       => $customerId,
                'new_customer_name' => $newCustomerName,
            ]
        );
    }

    public function customerId(): CustomerId|AggregateId
    {
        return CustomerId::fromString($this->content['customer_id']);
    }

    public function customerName(): CustomerName
    {
        return CustomerName::fromString($this->content['new_customer_name']);
    }
}
