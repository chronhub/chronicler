<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;

final class AccountSuspended extends AggregateChanged
{
    public static function forUser(AccountId $accountId,
                                   CustomerId $customerId,
                                   AccountStatus $newStatus,
                                   AccountStatus $oldStatus): self
    {
        return self::occur(
            $accountId->toString(),
            [
                'customer_id'    => $customerId->toString(),
                'current_status' => $newStatus->getValue(),
                'old_status'     => $oldStatus->getValue(),
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

    public function newStatus(): AccountStatus
    {
        return AccountStatus::byValue($this->content['new_status']);
    }

    public function oldStatus(): AccountStatus
    {
        return AccountStatus::byValue($this->content['old_status']);
    }
}
