<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\BankAccount;

use Chronhub\Chronicler\Stream\Stream;
use Illuminate\Contracts\Foundation\Application;
use Chronhub\Foundation\Reporter\Subscribers\HandleEvent;
use Chronhub\Foundation\Reporter\Subscribers\HandleCommand;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\DepositMade;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\MakeDepositHandler;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\RegisterCustomerHandler;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\RegisterBankAccountHandler;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\ChangeCustomerNameHandler;

class BankAccountTest extends AbstractBankAccountTest
{
    protected function createEventStreams(): void
    {
        $this->chronicler->persistFirstCommit(new Stream($this->customerStream));

        $this->chronicler->persistFirstCommit(new Stream($this->accountStream));
    }

    protected function eventStoreDriver(): string
    {
        return 'in_memory';
    }

    protected function setupConfiguration(Application $app): void
    {
        $app['config']->set('reporter.reporting.command.default', [
            'handler_method' => 'command',
            'messaging'      => [
                'decorators'  => [],
                'subscribers' => [
                    HandleCommand::class,
                ],
            ],
            'map'            => [
                'register-customer'     => RegisterCustomerHandler::class,
                'register-bank-account' => RegisterBankAccountHandler::class,
                'make-deposit'          => MakeDepositHandler::class,
                'change-customer-name'  => ChangeCustomerNameHandler::class,
            ],
        ]);

        $app['config']->set('reporter.reporting.event.default', [
            'handler_method' => 'onEvent',
            'messaging'      => [
                'subscribers' => [HandleEvent::class],
            ],
            'map'            => [
                'customer-registered'   => [
                    function (): void {
                        $this->customerRegistered = true;
                    }, ],
                'customer-name-changed' => [
                    function (): void {
                        $this->customerNameChanged = true;
                    }, ],
                'account-registered'    => [
                    function (): void {
                        $this->accountRegistered = true;
                    }, ],
                'deposit-made'          => [
                    function (DepositMade $event): void {
                        $this->currentBalance += $event->deposit();
                        ++$this->transactions;
                    }, ],
            ],
        ]);
    }

    protected function finalizeTest(): void
    {
    }
}
