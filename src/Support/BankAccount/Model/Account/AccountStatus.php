<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\BankAccount\Model\Account;

use Chronhub\Chronicler\Support\BankAccount\Model\Enum;

/**
 * @method static AccountStatus ACTIVE()
 * @method static AccountStatus SUSPENDED()
 */
final class AccountStatus extends Enum
{
    public const ACTIVE = 'active';
    public const SUSPENDED = 'suspended';
}
