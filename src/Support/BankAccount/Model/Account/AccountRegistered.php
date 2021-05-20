<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;

final class AccountRegistered extends AggregateChanged
{
    public static function forUser(AccountId $accountId,
                                   CustomerId $customerId,
                                   AccountStatus $status,
                                    Balance $balance): self
    {
        return self::occur(
            $accountId->toString(),
            [
                'customer_id'    => $customerId->toString(),
                'account_status' => $status->getValue(),
                'balance' => $balance->available(),
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

    public function accountStatus(): AccountStatus
    {
        return AccountStatus::byValue($this->content['account_status']);
    }

    public function balance(): Balance
    {
        return Balance::startAt($this->content['balance']);
    }
}
