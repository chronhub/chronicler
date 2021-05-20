<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;

final class WithdrawMade extends AggregateChanged
{
    public static function forUser(AccountId $accountId,
                                   CustomerId $customerId,
                                   int $withdraw,
                                   Balance $oldBalance): self
    {
        return self::occur(
            $accountId->toString(),
            [
                'customerId'  => $customerId->toString(),
                'old_balance' => $oldBalance->available(),
                'withdraw'     => $withdraw,
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

    public function withdraw(): int
    {
        return $this->content['withdraw'];
    }

    public function oldBalance(): Balance
    {
        return Balance::startAt($this->content['old_balance']);
    }
}
