<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Customer;

use Chronhub\Foundation\Message\DomainCommand;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

final class RegisterCustomer extends DomainCommand
{
    public static function withData(string $customerId, string $name): self
    {
        return new static(
            [
                'customer_id'   => $customerId,
                'customer_name' => $name,
            ]
        );
    }

    public function customerId(): CustomerId|AggregateId
    {
        return CustomerId::fromString($this->content['customer_id']);
    }

    public function customerName(): CustomerName
    {
        return CustomerName::fromString($this->content['customer_name']);
    }
}
