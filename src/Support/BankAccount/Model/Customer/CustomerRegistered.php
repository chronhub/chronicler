<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Customer;

use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

final class CustomerRegistered extends AggregateChanged
{
    public static function withCustomer(CustomerId $customerId, CustomerName $name): self
    {
        return self::occur($customerId->toString(), [
            'name' => $name->toString(),
        ]);
    }

    public function customerId(): CustomerId|AggregateId
    {
        return CustomerId::fromString($this->aggregateId());
    }

    public function customerName(): CustomerName
    {
        return CustomerName::fromString($this->content['name']);
    }
}
