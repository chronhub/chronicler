<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Customer;

use Chronhub\Foundation\Aggregate\HasAggregateIdentity;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

final class CustomerId implements AggregateId
{
    use HasAggregateIdentity;

    public function __toString(): string
    {
        return $this->toString();
    }
}
