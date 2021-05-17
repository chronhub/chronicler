<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;
use Chronhub\Foundation\Message\DomainCommand;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

final class RegisterBankAccount extends DomainCommand
{
    public static function withCustomer(string $accountId, string $customerId): self
    {
        return new self([
            'customer_id' => $customerId,
            'account_id' => $accountId
        ]);
    }

    public function customerId(): CustomerId|AggregateId
    {
        return CustomerId::fromString($this->content['customer_id']);
    }

    public function accountId(): AccountId|AggregateId
    {
        return AccountId::fromString($this->content['account_id']);
    }
}
