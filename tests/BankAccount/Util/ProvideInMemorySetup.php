<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\BankAccount\Util;

use Chronhub\Chronicler\Tracking\TrackStream;
use Chronhub\Foundation\Reporter\ReportEvent;
use Chronhub\Foundation\Reporter\ReportQuery;
use Chronhub\Foundation\Reporter\ReportCommand;
use Illuminate\Contracts\Foundation\Application;
use Chronhub\Chronicler\Tracking\TrackTransactionalStream;
use Chronhub\Chronicler\Tracking\Subscribers\PublishEvents;
use Chronhub\Foundation\Support\Contracts\Reporter\Reporter;
use Chronhub\Chronicler\Support\BankAccount\Model\Account\Account;
use Chronhub\Foundation\Support\Contracts\Reporter\ReporterManager;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\Customer;

trait ProvideInMemorySetup
{
    protected function availableInMemoryConfiguration(): array
    {
        return [
            'in_memory' => [
                'driver'   => 'in_memory',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'options'  => false,
            ],

            'transactional_in_memory' => [
                'driver'   => 'in_memory',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'options'  => [
                    'use_transaction'     => true,
                    'use_event_decorator' => false,
                ],
            ],

            'eventable_in_memory' => [
                'driver'   => 'in_memory',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'tracking' => [
                    'tracker_id'  => TrackStream::class,
                    'subscribers' => [PublishEvents::class],
                ],
                'options'  => [
                    'use_transaction'     => false,
                    'use_event_decorator' => true,
                ],
            ],

            'eventable_transactional_in_memory' => [
                'driver'   => 'in_memory',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'tracking' => [
                    'tracker_id'  => TrackTransactionalStream::class,
                    'subscribers' => [PublishEvents::class],
                ],
                'options'  => [
                    'use_transaction'     => false,
                    'use_event_decorator' => true,
                ],
            ],
        ];
    }

    protected function provideInMemoryConfig(Application $app, string $driver): void
    {
        $app['config']->set('chronicler.connections',
            [$driver => $this->availableInMemoryConfiguration()[$driver]]
        );
    }

    protected function provideRepositoriesConfig(Application $app, string $driver): void
    {
        $app['config']->set('chronicler.repositories.customer_stream', [
            'aggregate_type'   => Customer::class,
            'chronicler'       => $driver,
            'event_decorators' => [],
        ]);

        $app['config']->set('chronicler.repositories.account_stream', [
            'aggregate_type'   => Account::class,
            'chronicler'       => $driver,
            'event_decorators' => [],
        ]);
    }

    protected function registerDefaultReporters(Application $app): void
    {
        $app->bind(ReportCommand::class,
            fn (Application $app): Reporter => $app[ReporterManager::class]->command());

        $app->bind(ReportEvent::class,
            fn (Application $app): Reporter => $app[ReporterManager::class]->event());

        $app->bind(ReportQuery::class,
            fn (Application $app): Reporter => $app[ReporterManager::class]->query());
    }
}
