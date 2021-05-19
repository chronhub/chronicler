<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Foundation\Aggregate\HasAggregateIdentity;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

final class AccountId implements AggregateId
{
    use HasAggregateIdentity;

    public function __toString(): string
    {
        return $this->toString();
    }
}
