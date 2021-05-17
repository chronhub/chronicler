<?php

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateRoot;

interface AccountCollection
{
    /**
     * @param AccountId|AggregateId $accountId
     * @return Account|AggregateRoot
     */
    public function get(AccountId $accountId): null|Account|AggregateRoot;

    /**
     * @param Account $account
     */
    public function store(Account $account): void;
}
