<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Infrastructure;

use Chronhub\Chronicler\Support\BankAccount\Model\Customer\Customer;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerCollection;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateRepository;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;

final class CustomerChronicleStore implements CustomerCollection
{
    public function __construct(private AggregateRepository $repository)
    {
        //
    }

    public function get(CustomerId $customerId): null|Customer|AggregateRoot
    {
        return $this->repository->retrieve($customerId);
    }

    public function store(Customer $customer): void
    {
        $this->repository->persist($customer);
    }
}
