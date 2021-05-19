<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Infrastructure;

use Chronhub\Chronicler\Support\BankAccount\Model\Account\Account;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\AccountId;
use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateRepository;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\AccountCollection;

final class AccountChronicleStore implements AccountCollection
{
    public function __construct(private AggregateRepository $repository)
    {
    }

    public function get(AccountId $accountId): null|Account|AggregateRoot
    {
        return $this->repository->retrieve($accountId);
    }

    public function store(Account $account): void
    {
        $this->repository->persist($account);
    }
}
