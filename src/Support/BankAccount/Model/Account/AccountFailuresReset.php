<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;

final class AccountFailuresReset extends AggregateChanged
{
    public static function forUser(AccountId $accountId,
                                   CustomerId $customerId,
                                   int $newFailures,
                                   int $oldFailures): self
    {
        return self::occur(
            $accountId->toString(),
            [
                'customer_id'  => $customerId->toString(),
                'new_failures' => $newFailures,
                'old_failures' => $oldFailures,
            ]
        );
    }

    public function customerId(): CustomerId|AggregateId
    {
        return CustomerId::fromString($this->content['customer_id']);
    }

    public function accountId(): AccountId|AggregateId
    {
        return AccountId::fromString($this->aggregateId());
    }

    public function newFailures(): int
    {
        return $this->content['new_failures'];
    }

    public function oldFailures(): int
    {
        return $this->content['old_failures'];
    }
}
