<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\BankAccount;

use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Support\Facade\Publish;
use Illuminate\Contracts\Foundation\Application;
use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Chronicler\Tests\TestCaseWithOrchestra;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Foundation\Exception\MessageDispatchFailed;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Chronicler\Factory\ChroniclerServiceProvider;
use Chronhub\Chronicler\Driver\InMemory\InMemoryChronicler;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\AccountId;
use Chronhub\Chronicler\Support\Contracts\Factory\ChroniclerManager;
use Chronhub\Chronicler\Support\Contracts\Factory\RepositoryManager;
use Chronhub\Chronicler\Tests\BankAccount\Util\ProvideInMemorySetup;
use Chronhub\Foundation\Reporter\Services\FoundationServiceProvider;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\DepositMade;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\MakeDeposit;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;
use Chronhub\Chronicler\Driver\InMemory\InMemoryTransactionalChronicler;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\AccountCollection;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\AccountRegistered;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\RegisterCustomer;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\RegisterBankAccount;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerCollection;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerRegistered;
use Chronhub\Chronicler\Support\BankAccount\Infrastructure\AccountChronicleStore;
use Chronhub\Chronicler\Support\BankAccount\Infrastructure\CustomerChronicleStore;
use function class_exists;

abstract class AbstractBankAccountTest extends TestCaseWithOrchestra
{
    use ProvideInMemorySetup;

    protected Chronicler|InMemoryChronicler|InMemoryTransactionalChronicler $chronicler;
    protected CustomerCollection $customerRepository;
    protected AccountCollection $accountRepository;
    protected StreamName $accountStream;
    protected StreamName $customerStream;
    protected CustomerId|AggregateId $customerId;
    protected AccountId|AggregateId $accountId;

    // event handler result
    protected bool $customerRegistered = false;
    protected bool $accountRegistered = false;
    protected int $currentBalance = 0;
    protected int $transactions = 0;

    /**
     * @test
     */
    public function it_test_bank_account(): void
    {
        $this->createEventStreams();

        $this->dispatchCommands();

        $this->test_customer_aggregate();

        $this->test_account_aggregate();

        $this->test_chronicler_events();

        if ($this->chronicler instanceof InMemoryChronicler) {
            $recordedEvents = $this->chronicler->pullCachedRecordedEvents();

            $this->assertCount(12, $recordedEvents);

            foreach ($recordedEvents as $recordedEvent) {
                Publish::event($recordedEvent);
            }
        }

        $this->assertTrue($this->customerRegistered);
        $this->assertTrue($this->accountRegistered);
        $this->assertEquals(10, $this->transactions);
        $this->assertEquals(1000, $this->currentBalance);
    }

    private function dispatchCommands(): void
    {
        try {
            $registerCustomer = RegisterCustomer::withData($this->customerId->toString(), 'steph bug');
            Publish::command($registerCustomer);

            // make a PM
            $registerBankAccount = RegisterBankAccount::withCustomer(
                $this->accountId->toString(), $this->customerId->toString()
            );

            Publish::command($registerBankAccount);

            $num = 10;
            while (0 !== $num) {
                $makeDeposit = MakeDeposit::withCustomer(
                    $this->customerId->toString(), $this->accountId->toString(), 100
                );

                Publish::command($makeDeposit);

                --$num;
            }
        } catch (MessageDispatchFailed $exception) {
            throw $exception->getPrevious();
        }
    }

    private function test_customer_aggregate(): void
    {
        $customer = $this->customerRepository->get($this->customerId);

        $this->assertEquals($this->customerId, $customer->aggregateId());
        $this->assertEquals($this->customerId, $customer->customerId());
        $this->assertEquals(1, $customer->version());
        $this->assertEquals('steph bug', $customer->name()->toString());
    }

    private function test_account_aggregate(): void
    {
        $account = $this->accountRepository->get($this->accountId);

        $this->assertEquals($this->customerId, $account->customerId());
        $this->assertEquals($this->accountId, $account->aggregateId());
        $this->assertEquals($this->accountId, $account->accountId());
        $this->assertEquals(11, $account->version());
        $this->assertEquals(1000, $account->balance()->available());
    }

    private function test_chronicler_events(): void
    {
        $customerEvents = iterator_to_array($this->chronicler->retrieveAll($this->customerStream, $this->customerId));

        $this->assertCount(1, $customerEvents);
        $this->assertInstanceOf(CustomerRegistered::class, $customerEvents[0]);
        $this->test_aggregate_changed_header($customerEvents[0]);

        $accountEvents = iterator_to_array($this->chronicler->retrieveAll($this->accountStream, $this->accountId));

        $this->assertCount(11, $accountEvents);
        $this->assertInstanceOf(AccountRegistered::class, $accountEvents[0]);
        $this->assertInstanceOf(DepositMade::class, $accountEvents[10]);
        $this->test_aggregate_changed_header($accountEvents[0]);
    }

    private function test_aggregate_changed_header(AggregateChanged $event): void
    {
        $this->assertArrayHasKey(Header::EVENT_ID, $event->headers());
        $this->assertIsString($event->header(Header::EVENT_ID));

        $this->assertArrayHasKey(Header::EVENT_TYPE, $event->headers());
        $this->assertTrue(class_exists($event->header(Header::EVENT_TYPE)));

        $this->assertArrayHasKey(Header::EVENT_TIME, $event->headers());
        $this->assertIsString($event->header(Header::EVENT_TIME));

        $this->assertArrayHasKey(Header::AGGREGATE_ID, $event->headers());

        $this->assertArrayHasKey(Header::AGGREGATE_ID_TYPE, $event->headers());
        $this->assertTrue(class_exists($event->header(Header::AGGREGATE_ID_TYPE)));

        $this->assertArrayHasKey(Header::AGGREGATE_VERSION, $event->headers());
        $this->assertArrayHasKey(Header::INTERNAL_POSITION, $event->headers());
    }

    protected function getPackageProviders($app): array
    {
        return [
            FoundationServiceProvider::class,
            ChroniclerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $this->setupConfiguration($app);

        $this->registerDefaultReporters($app);

        $this->setupServices($app, $this->eventStoreDriver());
    }

    protected function setupServices(Application $app, string $chroniclerDriver): void
    {
        $app->singleton(config('chronicler.provider.in_memory'));

        $this->provideInMemoryConfig($app, $chroniclerDriver);

        $this->provideRepositoriesConfig($app, $chroniclerDriver);

        $app->singleton(Chronicler::class, function (Application $app) use ($chroniclerDriver): Chronicler {
            return $app[ChroniclerManager::class]->create($chroniclerDriver);
        });

        $this->chronicler = $app[Chronicler::class];

        $this->customerStream = new StreamName('customer_stream');
        $this->accountStream = new StreamName('account_stream');

        $app->singleton(CustomerCollection::class, function (Application $app): CustomerCollection {
            $repository = $app[RepositoryManager::class]->create($this->customerStream->toString());

            return new CustomerChronicleStore($repository);
        });

        $app->singleton(AccountCollection::class, function (Application $app): AccountCollection {
            $repository = $app[RepositoryManager::class]->create($this->accountStream->toString());

            return new AccountChronicleStore($repository);
        });

        $this->customerRepository = $app[CustomerCollection::class];
        $this->accountRepository = $app[AccountCollection::class];

        $this->customerId = CustomerId::create();
        $this->accountId = AccountId::create();
    }

    abstract protected function setupConfiguration(Application $app): void;

    abstract protected function eventStoreDriver(): string;

    abstract protected function createEventStreams(): void;

    abstract protected function finalizeTest(): void;
}
