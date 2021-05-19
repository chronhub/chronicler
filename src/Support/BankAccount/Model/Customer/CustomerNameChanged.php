<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Customer;

use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

final class CustomerNameChanged extends AggregateChanged
{
    public static function forCustomer(CustomerId $customerId, CustomerName $newName, CustomerName $oldName): self
    {
        return self::occur($customerId->toString(), [
            'new_name' => $newName->toString(),
            'old_name' => $oldName->toString(),
        ]);
    }

    public function customerId(): CustomerId|AggregateId
    {
        return CustomerId::fromString($this->aggregateId());
    }

    public function newCustomerName(): CustomerName
    {
        return CustomerName::fromString($this->content['new_name']);
    }

    public function oldCustomerName(): CustomerName
    {
        return CustomerName::fromString($this->content['old_name']);
    }
}
