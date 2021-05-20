<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\BankAccount;

use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Support\Facade\Publish;
use Illuminate\Contracts\Foundation\Application;
use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Chronicler\Tests\TestCaseWithOrchestra;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Foundation\Exception\MessageDispatchFailed;
use Chronhub\Foundation\Reporter\Subscribers\HandleEvent;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Chronicler\Factory\ChroniclerServiceProvider;
use Chronhub\Chronicler\Driver\InMemory\InMemoryChronicler;
use Chronhub\Foundation\Reporter\Subscribers\HandleCommand;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Support\Contracts\Factory\ChroniclerManager;
use Chronhub\Chronicler\Support\Contracts\Factory\RepositoryManager;
use Chronhub\Chronicler\Tests\BankAccount\Util\ProvideInMemorySetup;
use Chronhub\Foundation\Reporter\Services\FoundationServiceProvider;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;
use Chronhub\Chronicler\Driver\InMemory\InMemoryTransactionalChronicler;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\RegisterCustomer;
use Chronhub\Chronicler\Tracking\Subscribers\TransactionalHandlerSubscriber;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\ChangeCustomerName;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerCollection;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerRegistered;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerNameChanged;
use Chronhub\Chronicler\Support\BankAccount\Infrastructure\CustomerChronicleStore;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\RegisterCustomerHandler;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\ChangeCustomerNameHandler;

final class ItDispatchCommandOnEventTest extends TestCaseWithOrchestra
{
    use ProvideInMemorySetup;

    protected Chronicler|InMemoryChronicler|InMemoryTransactionalChronicler $chronicler;
    protected CustomerCollection $customerRepository;
    protected StreamName $customerStream;
    protected CustomerId|AggregateId $customerId;
    protected bool $customerRegistered = false;
    protected bool $customerNameChanged = false;

    /**
     * @test
     */
    public function it_test_bank_account(): void
    {
        $this->createEventStreams();

        $this->dispatchCommands();

        $this->assertTrue($this->customerRegistered);
        $this->assertTrue($this->customerNameChanged);

        $this->test_customer_aggregate();

        $this->test_chronicler_events();
    }

    private function dispatchCommands(): void
    {
        try {
            $registerCustomer = RegisterCustomer::withData(
                $this->customerId->toString(), 'steph bug'
            );

            Publish::command($registerCustomer);
        } catch (MessageDispatchFailed $exception) {
            throw $exception->getPrevious();
        }
    }

    private function test_customer_aggregate(): void
    {
        $customer = $this->customerRepository->get($this->customerId);

        $this->assertEquals($this->customerId, $customer->aggregateId());
        $this->assertEquals($this->customerId, $customer->customerId());
        $this->assertEquals(2, $customer->version());
        $this->assertEquals('walter white', $customer->name()->toString());
    }

    private function test_chronicler_events(): void
    {
        $customerEvents = iterator_to_array($this->chronicler->retrieveAll($this->customerStream, $this->customerId));

        $this->assertCount(2, $customerEvents);
        $this->assertInstanceOf(CustomerRegistered::class, $customerEvents[0]);
        $this->test_aggregate_changed_header($customerEvents[0]);
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

    protected function defineEnvironment($app): void
    {
        $this->registerDefaultReporters($app);

        $this->setupConfiguration($app);

        $this->setupServices($app);
    }

    protected function setupServices(Application $app): void
    {
        $app->singleton(Chronicler::class, function (Application $app): Chronicler {
            return $this->createEventStore($app);
        });

        $this->chronicler = $app[Chronicler::class];

        $this->customerStream = new StreamName('customer_stream');

        $app->singleton(CustomerCollection::class, function (Application $app): CustomerCollection {
            $repository = $app[RepositoryManager::class]->create($this->customerStream->toString());

            return new CustomerChronicleStore($repository);
        });

        $this->customerRepository = $app[CustomerCollection::class];

        $this->customerId = CustomerId::create();
    }

    protected function createEventStreams(): void
    {
        $customerStreamCreated = $this->chronicler->transactional(
            function (InMemoryTransactionalChronicler $chronicler): void {
                $chronicler->persistFirstCommit(new Stream($this->customerStream));
            });

        $this->assertTrue($customerStreamCreated);
    }

    protected function createEventStore(Application $app): Chronicler
    {
        return $app[ChroniclerManager::class]->create('eventable_transactional_in_memory');
    }

    protected function getPackageProviders($app): array
    {
        return [
            FoundationServiceProvider::class,
            ChroniclerServiceProvider::class,
        ];
    }

    protected function setupConfiguration(Application $app): void
    {
        $app['config']->set('reporter.reporting.command.default', [
            'handler_method' => 'command',
            'messaging'      => [
                'decorators'  => [],
                'subscribers' => [
                    HandleCommand::class,
                    TransactionalHandlerSubscriber::class,
                ],
            ],
            'map'            => [
                'register-customer'    => RegisterCustomerHandler::class,
                'change-customer-name' => ChangeCustomerNameHandler::class,
            ],
        ]);

        $app['config']->set('reporter.reporting.event.default', [
            'handler_method' => 'onEvent',
            'messaging'      => [
                'subscribers' => [HandleEvent::class],
            ],
            'map'            => [
                'customer-registered'   => [
                    function (CustomerRegistered $event): void {
                        $this->customerRegistered = true;

                        Publish::command(
                            ChangeCustomerName::withCustomer(
                                $event->aggregateId(), 'walter white'
                            )
                        );
                    },
                ],
                'customer-name-changed' => [
                    function (CustomerNameChanged $event): void {
                        $this->customerNameChanged = true;
                        $this->assertEquals('walter white', $event->newCustomerName()->toString());
                        $this->assertEquals('steph bug', $event->oldCustomerName()->toString());
                    }, ],
            ],
        ]);

        $this->provideInMemoryConfig($app, 'eventable_transactional_in_memory');
        $this->provideRepositoriesConfig($app, 'eventable_transactional_in_memory');
    }
}
