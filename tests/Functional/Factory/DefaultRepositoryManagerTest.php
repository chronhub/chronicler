<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Functional\Factory;

use Chronhub\Chronicler\Exception\RuntimeException;
use Chronhub\Chronicler\Tests\TestCaseWithOrchestra;
use Chronhub\Chronicler\Factory\ChroniclerServiceProvider;
use Chronhub\Chronicler\Aggregate\GenericAggregateRepository;
use Chronhub\Chronicler\Support\BankAccount\Model\Customer\Customer;
use Chronhub\Chronicler\Support\Contracts\Factory\RepositoryManager;

/** @coversDefaultClass \Chronhub\Foundation\Reporter\Services\DefaultReporterManager */
final class DefaultRepositoryManagerTest extends TestCaseWithOrchestra
{
    protected function getPackageProviders($app): array
    {
        return [ChroniclerServiceProvider::class];
    }

    /**
     * @test
     */
    public function it_assert_binding(): void
    {
        $this->assertTrue($this->app->bound(RepositoryManager::class));
    }

    /**
     * @test
     */
    public function it_assert_default_configuration(): void
    {
        $config = [
            'use_foundation_decorators' => true,
            'event_decorators' => [],
            'repositories' => [
                'my_stream_name' => [
                    'aggregate_type' => [
                        'root' => 'AG class name',
                        'children' => [],
                    ],
                    'chronicler' => 'default',
                    'cache' => [
                        'driver' => 'null',
                        'max' => 10000,
                    ],
                    'event_decorators' => [],
                    'snapshot' => [
                        'use_snapshot' => false,
                        'stream_name' => null,
                        'store' => 'snapshot.store.service.id',
                        'repository' => 'AggregateRepositoryWithSnapshot',
                        'persist_every_x_events' => 1000,
                        'projector' => [
                            'name' => 'default',
                            'options' => 'lazy',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($config['use_foundation_decorators'], $this->app['config']->get('chronicler.use_foundation_decorators'));
        $this->assertEquals($config['event_decorators'], $this->app['config']->get('chronicler.event_decorators'));
        $this->assertEquals($config['repositories'], $this->app['config']->get('chronicler.repositories'));
    }

    /**
     * @test
     */
    public function it_raise_exception_with_not_found_stream_name(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid repository config for stream name invalid_stream_name');

        $this->app[RepositoryManager::class]->create('invalid_stream_name');
    }

    /**
     * @test
     */
    public function it_raise_exception_with_empty_repository_config(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid repository config for stream name invalid_stream_name');

        $this->app['config']->set('chronicler.repositories', [
            'customer_stream' => [],
        ]);

        $this->app[RepositoryManager::class]->create('invalid_stream_name');
    }

    /**
     * @test
     */
    public function it_raise_exception_with_invalid_aggregate_root(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid repository config for stream name invalid_stream_name');

        $this->app['config']->set('chronicler.repositories', [
            'customer_stream' => [],
            'aggregate_type' => [
                'root' => 'AG class name',
                'children' => [],
            ],
        ]);

        $this->app[RepositoryManager::class]->create('invalid_stream_name');
    }

    /**
     * @test
     */
    public function it_raise_exception_with_invalid_chronicler_driver(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Chronicle store connection invalid_driver not found');

        $this->app['config']->set('chronicler.repositories', [
            'customer_stream' => [
                'chronicler' => 'invalid_driver',
                'aggregate_type' => [
                    'root' => Customer::class,
                    'children' => [],
                ],
            ],
        ]);

        $this->app[RepositoryManager::class]->create('customer_stream');
    }

    /**
     * @test
     */
    public function it_create_aggregate_repository_with_array_aggregate_type(): void
    {
        $this->app['config']->set('chronicler.repositories', [
            'customer_stream' => [
                'chronicler' => 'in_memory',
                'aggregate_type' => [
                    'root' => Customer::class,
                    'children' => [],
                ],
            ],
        ]);

        $repository = $this->app[RepositoryManager::class]->create('customer_stream');

        $this->assertInstanceOf(GenericAggregateRepository::class, $repository);
    }

    /**
     * @test
     */
    public function it_create_aggregate_repository(): void
    {
        $this->app['config']->set('chronicler.repositories', [
            'customer_stream' => [
                'chronicler' => 'in_memory',
                'aggregate_type' => Customer::class,
            ],
        ]);

        $repository = $this->app[RepositoryManager::class]->create('customer_stream');

        $this->assertInstanceOf(GenericAggregateRepository::class, $repository);
    }

    /**
     * @test
     */
    public function it_return_same_aggregate_repository_instance(): void
    {
        $this->app['config']->set('chronicler.repositories', [
            'customer_stream' => [
                'chronicler' => 'in_memory',
                'aggregate_type' => Customer::class,
            ],
        ]);

        $repository = $this->app[RepositoryManager::class]->create('customer_stream');

        $this->assertEquals($repository,
            $this->app[RepositoryManager::class]->create('customer_stream')
        );
    }
}
