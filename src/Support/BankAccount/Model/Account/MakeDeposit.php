<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;
use Chronhub\Foundation\Message\DomainCommand;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use RuntimeException;

final class MakeDeposit extends DomainCommand
{
    public static function withCustomer(string $customerId, string $accountId, int $deposit): self
    {
        return new self([
            'customer_id' => $customerId,
            'account_id'  => $accountId,
            'deposit'     => $deposit
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

    public function deposit(): int
    {
        $deposit = $this->content['deposit'];

        if ($deposit < 1) {
            throw new RuntimeException("Deposit must be greater than 0");
        }

        return $deposit;
    }
}
