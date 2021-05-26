<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;

final class DepositMade extends AggregateChanged
{
    public static function forUser(AccountId $accountId,
                                   CustomerId $customerId,
                                   int $deposit,
                                   Balance $oldBalance): self
    {
        return self::occur(
            $accountId->toString(),
            [
                'customer_id'  => $customerId->toString(),
                'old_balance' => $oldBalance->available(),
                'deposit'     => $deposit,
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

    public function deposit(): int
    {
        return $this->content['deposit'];
    }

    public function oldBalance(): Balance
    {
        return Balance::startAt($this->content['old_balance']);
    }
}
