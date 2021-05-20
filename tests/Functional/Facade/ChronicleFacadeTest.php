<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Functional\Facade;

use Chronhub\Chronicler\Support\Facade\Chronicle;
use Chronhub\Chronicler\Tests\TestCaseWithOrchestra;
use Chronhub\Chronicler\Factory\ChroniclerServiceProvider;
use Chronhub\Chronicler\Driver\InMemory\InMemoryChronicler;
use Chronhub\Chronicler\Driver\InMemory\InMemoryTransactionalChronicler;

final class ChronicleFacadeTest extends TestCaseWithOrchestra
{
    protected function getPackageProviders($app): array
    {
        return [ChroniclerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('chronicler.connections', [
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
        ]);
    }

    /**
     * @test
     */
    public function it_test_instance(): void
    {
        $this->assertTrue($this->app->bound(Chronicle::SERVICE_NAME));

        $chronicler = Chronicle::create('in_memory');

        $this->assertInstanceOf(InMemoryChronicler::class, $chronicler);

        $transactionalChronicler = Chronicle::create('transactional_in_memory');

        $this->assertInstanceOf(InMemoryTransactionalChronicler::class, $transactionalChronicler);
    }
}
