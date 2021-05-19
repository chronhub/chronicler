<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use RuntimeException;
use Chronhub\Foundation\Message\DomainCommand;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;

final class MakeWithdraw extends DomainCommand
{
    public static function withCustomer(string $customerId, string $accountId, int $withdraw): self
    {
        return new self([
            'customer_id' => $customerId,
            'account_id'  => $accountId,
            'withdraw'    => $withdraw,
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

    public function withdraw(): int
    {
        $withdraw = $this->content['withdraw'];

        if ($withdraw < 1) {
            throw new RuntimeException('Withdraw must be greater than 0');
        }

        return $withdraw;
    }
}
