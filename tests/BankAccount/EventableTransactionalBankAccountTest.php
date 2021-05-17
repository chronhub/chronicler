<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\BankAccount;

use Chronhub\Chronicler\Driver\InMemory\InMemoryTransactionalChronicler;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\DepositMade;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\MakeDepositHandler;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\RegisterBankAccountHandler;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\RegisterCustomerHandler;
use Chronhub\Chronicler\Tracking\Subscribers\TransactionalHandlerSubscriber;
use Chronhub\Foundation\Reporter\Subscribers\HandleCommand;
use Chronhub\Foundation\Reporter\Subscribers\HandleEvent;
use Illuminate\Contracts\Foundation\Application;

final class EventableTransactionalBankAccountTest extends AbstractBankAccountTest
{
    protected function createEventStreams(): void
    {
        $customerStreamCreated = $this->chronicler->transactional(
            function (InMemoryTransactionalChronicler $chronicler): void {
                $chronicler->persistFirstCommit(new Stream($this->customerStream));
            });

        $this->assertTrue($customerStreamCreated);

        $accountStreamCreated = $this->chronicler->transactional(
            function (InMemoryTransactionalChronicler $chronicler): void {
                $chronicler->persistFirstCommit(new Stream($this->accountStream));
            });

        $this->assertTrue($accountStreamCreated);
    }

    protected function eventStoreDriver(): string
    {
        return 'eventable_transactional_in_memory';
    }

    protected function setupConfiguration(Application $app): void
    {
        $app['config']->set('reporter.reporting.command.default', [
            'handler_method' => 'command',
            'messaging'      => [
                'decorators'  => [],
                'subscribers' => [
                    HandleCommand::class,
                    TransactionalHandlerSubscriber::class
                ],
            ],
            'map'            => [
                'register-customer'     => RegisterCustomerHandler::class,
                'register-bank-account' => RegisterBankAccountHandler::class,
                'make-deposit'          => MakeDepositHandler::class,
            ]
        ]);

        $app['config']->set('reporter.reporting.event.default', [
            'handler_method' => 'onEvent',
            'messaging'      => [
                'subscribers' => [HandleEvent::class],
            ],
            'map'            => [
                'customer-registered' => [
                    function () {
                        $this->customerRegistered = true;
                    },
                ],
                'account-registered'  => [
                    function () {
                        $this->accountRegistered = true;
                    }],
                'deposit-made'        => [
                    function (DepositMade $event) {
                        $this->currentBalance += $event->deposit();
                        $this->transactions++;
                    }],
            ]
        ]);
    }

    protected function finalizeTest(): void
    {
        //
    }
}
