<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Functional\Factory;

use Chronhub\Chronicler\Factory\ChroniclerServiceProvider;
use Chronhub\Chronicler\Factory\EventStoreServiceProvider;
use Chronhub\Chronicler\Support\Contracts\Factory\ChroniclerManager;
use Chronhub\Chronicler\Support\Contracts\Factory\RepositoryManager;
use Chronhub\Chronicler\Support\Contracts\Support\JsonEncoder;
use Chronhub\Chronicler\Support\Facade\Chronicle;
use Chronhub\Chronicler\Tests\TestCaseWithOrchestra;

/** @coversDefaultClass \Chronhub\Chronicler\Factory\EventStoreServiceProvider */
final class EventStoreServiceProviderTest extends TestCaseWithOrchestra
{
    protected function getPackageProviders($app): array
    {
        return [EventStoreServiceProvider::class];
    }

    /**
     * @test
     */
    public function it_test_bindings(): void
    {
        $this->assertTrue($this->app->bound(ChroniclerManager::class));
        $this->assertTrue($this->app->bound(Chronicle::SERVICE_NAME));
        $this->assertTrue($this->app->bound(RepositoryManager::class));

        $this->assertTrue($this->app->bound(JsonEncoder::class));
    }

    /**
     * @test
     */
    public function it_test_provides(): void
    {
        $this->assertEquals([
            ChroniclerManager::class,
            Chronicle::SERVICE_NAME,
            RepositoryManager::class,
            JsonEncoder::class,
        ], $this->app->getProvider(EventStoreServiceProvider::class)->provides());
    }
}
