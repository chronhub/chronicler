<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\BankAccount;

use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Message\DomainEvent;
use Chronhub\Foundation\Message\DomainCommand;
use Chronhub\Foundation\Support\Facade\Publish;
use Illuminate\Contracts\Foundation\Application;
use Chronhub\Chronicler\Tests\TestCaseWithOrchestra;
use Chronhub\Chronicler\Support\Contracts\Chronicler;
use Chronhub\Foundation\Exception\MessageDispatchFailed;
use Chronhub\Foundation\Reporter\Subscribers\HandleEvent;
use Chronhub\Foundation\Support\Contracts\Message\Header;
use Chronhub\Chronicler\Factory\ChroniclerServiceProvider;
use Chronhub\Foundation\Reporter\Subscribers\HandleCommand;
use Chronhub\Chronicler\Support\Contracts\EventableChronicler;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;
use Chronhub\Chronicler\Tracking\Subscribers\MarkCausationCommand;
use Chronhub\Chronicler\Support\Contracts\Factory\ChroniclerManager;
use Chronhub\Chronicler\Support\Contracts\Factory\RepositoryManager;
use Chronhub\Chronicler\Tests\BankAccount\Util\ProvideInMemorySetup;
use Chronhub\Foundation\Reporter\Services\FoundationServiceProvider;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerId;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\RegisterCustomer;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\ChangeCustomerName;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerCollection;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerRegistered;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\CustomerNameChanged;
use Chronhub\Chronicler\Support\BankAccount\Infrastructure\CustomerChronicleStore;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\RegisterCustomerHandler;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\ChangeCustomerNameHandler;

final class ItDecorateHeaderWithCausationCommandTest extends TestCaseWithOrchestra
{
    use ProvideInMemorySetup;

    protected EventableChronicler $chronicler;
    protected CustomerCollection $customerRepository;
    protected StreamName $customerStream;
    protected CustomerId|AggregateId $customerId;
    protected bool $customerRegistered = false;
    protected int $customerNameChanged = 0;
    protected array $commands = [
        'register' => null,
        'names'    => [],
    ];

    /**
     * @test
     */
    public function it_test_bank_account(): void
    {
        $this->createEventStreams();

        $this->dispatchCommands();

        $this->test_chronicler_events();

        $this->assertTrue($this->customerRegistered);

        $this->assertEquals(2, $this->customerNameChanged);
    }

    private function dispatchCommands(): void
    {
        try {
            $registerCustomer = RegisterCustomer::withData(
                $this->customerId->toString(), 'steph bug'
            );

            Publish::command($registerCustomer);

            Publish::command(
                ChangeCustomerName::withCustomer(
                    $this->customerId->toString(), 'walter white'
                )
            );
        } catch (MessageDispatchFailed $exception) {
            throw $exception->getPrevious();
        }
    }

    private function test_chronicler_events(): void
    {
        $customerEvents = iterator_to_array($this->chronicler->retrieveAll($this->customerStream, $this->customerId));

        $this->assertCount(3, $customerEvents);

        $this->assertInstanceOf(CustomerRegistered::class, $customerEvents[0]);
        $this->test_causation_register($customerEvents[0]);

        foreach ($this->commands['names'] as $key => $command) {
            $event = $customerEvents[$key + 1];

            $this->assertInstanceOf(CustomerNameChanged::class, $event);
            $this->test_causation_name($event, $command);

            1 === $key
                ? $this->assertEquals('walter white', $event->newCustomerName()->toString())
                : $this->assertEquals('heisenberg', $event->newCustomerName()->toString());
        }
    }

    private function test_causation_register(DomainEvent $event): void
    {
        $this->assertArrayHasKey(Header::EVENT_CAUSATION_ID, $event->headers());
        $this->assertArrayHasKey(Header::EVENT_CAUSATION_TYPE, $event->headers());

        $this->assertEquals(
            $this->commands['register']->header(Header::EVENT_ID)->toString(),
            $event->Header(Header::EVENT_CAUSATION_ID)
        );

        $this->assertEquals(
            $this->commands['register']->header(Header::EVENT_TYPE),
            $event->Header(Header::EVENT_CAUSATION_TYPE)
        );
    }

    private function test_causation_name(DomainEvent $event, DomainCommand $command): void
    {
        $this->assertArrayHasKey(Header::EVENT_CAUSATION_ID, $event->headers());
        $this->assertArrayHasKey(Header::EVENT_CAUSATION_TYPE, $event->headers());

        $this->assertEquals(
            $command->header(Header::EVENT_ID)->toString(),
            $event->Header(Header::EVENT_CAUSATION_ID)
        );

        $this->assertEquals(
            $command->header(Header::EVENT_TYPE),
            $event->Header(Header::EVENT_CAUSATION_TYPE)
        );
    }

    protected function defineEnvironment($app): void
    {
        $this->setupConfiguration($app);

        $this->setupServices($app);
    }

    protected function setupServices(Application $app): void
    {
        $this->registerDefaultReporters($app);

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
        $this->chronicler->persistFirstCommit(new Stream($this->customerStream));
    }

    protected function createEventStore(Application $app): Chronicler
    {
        return $app[ChroniclerManager::class]->create('eventable_in_memory');
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
                    MarkCausationCommand::class,
                ],
            ],
            'map'            => [
                'register-customer'    => function (RegisterCustomer $command): void {
                    $this->commands['register'] = $command;
                    app()->make(RegisterCustomerHandler::class)->command($command);
                },
                'change-customer-name' => function (ChangeCustomerName $command): void {
                    if (isset($this->commands['names'][0])) {
                        $this->commands['names'][1] = $command;
                    } else {
                        $this->commands['names'][0] = $command;
                    }

                    app()->make(ChangeCustomerNameHandler::class)->command($command);
                },
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
                                $event->aggregateId(), 'heisenberg'
                            )
                        );
                    },
                ],
                'customer-name-changed' => [
                    function (): void {
                        ++$this->customerNameChanged;
                    }, ],
            ],
        ]);

        $this->provideInMemoryConfig($app, 'eventable_in_memory');

        $this->provideRepositoriesConfig($app, 'eventable_in_memory');
    }
}
