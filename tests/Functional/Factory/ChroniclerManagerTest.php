<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Functional\Factory;

use Chronhub\Chronicler\Driver\InMemory\InMemoryChronicler;
use Chronhub\Chronicler\Driver\InMemory\InMemoryTransactionalChronicler;
use Chronhub\Chronicler\Exception\InvalidArgumentException;
use Chronhub\Chronicler\Exception\RuntimeException;
use Chronhub\Chronicler\Factory\ChroniclerServiceProvider;
use Chronhub\Chronicler\Support\Contracts\Factory\ChroniclerManager;
use Chronhub\Chronicler\Tests\TestCaseWithOrchestra;
use Illuminate\Contracts\Foundation\Application;

/** @coversDefaultClass \Chronhub\Chronicler\Factory\DefaultChroniclerManager */
final class ChroniclerManagerTest extends TestCaseWithOrchestra
{
    protected function getPackageProviders($app): array
    {
        return [ChroniclerServiceProvider::class];
    }

    /**
     * @test
     */
    public function it_raise_exception_with_empty_name(): void
    {
        $this->expectExceptionMessage(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid chronicler name');

        $this->app[ChroniclerManager::class]->create('');
    }

    /**
     * @test
     */
    public function it_raise_exception_when_service_name_not_found(): void
    {
        $this->expectExceptionMessage(InvalidArgumentException::class);
        $this->expectExceptionMessage('Chronicle store connection not_configured_service not found');

        $this->app['config']->set('chronicler.connections', []);

        $this->app[ChroniclerManager::class]->create('not_configured_service');
    }

    /**
     * @test
     */
    public function it_resolve_default_service(): void
    {
        $this->app['config']->set('chronicler.connections', [
            'default' => 'in_memory',
            'in_memory' => [
                'driver'   => 'in_memory',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'options'  => false,
            ],
        ]);

        $defaultChronicler = $this->app[ChroniclerManager::class]->create('default');
        $inMemoryChronicler = $this->app[ChroniclerManager::class]->create('in_memory');

        $this->assertEquals($defaultChronicler, $inMemoryChronicler);
    }

    /**
     * @test
     */
    public function it_resolve_transactional_in_memory_chronicler(): void
    {
        $this->app['config']->set('chronicler.connections', [
            'transactional_in_memory' => [
                'driver'   => 'in_memory',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'options'  => [
                    'use_transaction' => true,
                ],
            ],
        ]);

        $chronicler = $this->app[ChroniclerManager::class]->create('transactional_in_memory');

        $this->assertInstanceOf(InMemoryTransactionalChronicler::class, $chronicler);
    }

    /**
     * @test
     */
    public function it_raise_exception_with_invalid_use_transaction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to configure chronicler event decorator');

        $this->app['config']->set('chronicler.connections', [
            'transactional_in_memory' => [
                'driver'   => 'in_memory',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'options'  => [
                    'use_transaction' => false,
                ],
            ],
        ]);

        $this->app[ChroniclerManager::class]->create('transactional_in_memory');
    }

    /**
     * @test
     */
    public function it_raise_exception_with_invalid_stream_provider_given(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to determine stream provider');

        $this->app['config']->set('chronicler.connections', [
            'in_memory' => [
                'driver'   => 'in_memory',
                'strategy' => 'single',
                'provider' => 'invalid_provider',
                'options'  => false,
            ],
        ]);

        $this->app[ChroniclerManager::class]->create('in_memory');
    }

    /**
     * @test
     */
    public function it_raise_exception_with_invalid_driver(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve chronicle store with name in_memory and driver invalid_driver');

        $this->app['config']->set('chronicler.connections', [
            'in_memory' => [
                'driver'   => 'invalid_driver',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'options'  => false,
            ],
        ]);

        $this->app[ChroniclerManager::class]->create('in_memory');
    }

    /**
     * @test
     */
    public function it_can_be_extended(): void
    {
        $this->app['config']->set('chronicler.connections', [
            'my_in_memory' => [
                'driver'   => 'in_memory',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'options'  => false,
            ],

            'in_memory' => [
                'driver'   => 'in_memory',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'options'  => false,
            ],
        ]);

        $refConfig = null;
        $this->app[ChroniclerManager::class]->extends('my_in_memory',
            function (Application $app, array $config) use (&$refConfig) {
                $refConfig = $config;

                return $app[ChroniclerManager::class]->create('in_memory');
            });

        $chronicler = $this->app[ChroniclerManager::class]->create('my_in_memory');

        $this->assertInstanceOf(InMemoryChronicler::class, $chronicler);
        $this->assertEquals($this->app['config']->get('chronicler'), $refConfig);
    }

    /**
     * @test
     */
    public function it_attach_stream_subscriber_to_chronicler_tracker(): void
    {
        // set tracker eventable decorator
        //$this->app['config']->set('chronicler.')


        $this->app['config']->set('chronicler.connections', [
            'my_in_memory' => [
                'driver'   => 'in_memory',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'options'  => false,
            ],

            'in_memory' => [
                'driver'   => 'in_memory',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'options'  => false,
            ],
        ]);
    }
}
