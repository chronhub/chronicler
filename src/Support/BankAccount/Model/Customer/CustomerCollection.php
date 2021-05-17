<?php

namespace Chronhub\Chronicler\Support\BankAccount\Model\Customer;

use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;

interface CustomerCollection
{
    /**
     * @param CustomerId|AggregateId $customerId
     * @return Customer|AggregateRoot
     */
    public function get(CustomerId $customerId): null|Customer|AggregateRoot;

    /**
     * @param Customer $customer
     */
    public function store(Customer $customer): void;
}
