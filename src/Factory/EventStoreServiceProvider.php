<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Factory;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Chronhub\Chronicler\Support\Facade\Chronicle;
use Illuminate\Contracts\Support\DeferrableProvider;
use Chronhub\Chronicler\Support\Contracts\Support\JsonEncoder;
use Chronhub\Chronicler\Support\Contracts\Factory\ChroniclerManager;
use Chronhub\Chronicler\Support\Contracts\Factory\RepositoryManager;

final class EventStoreServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var Application
     */
    public $app;

    public function register(): void
    {
        $this->app->singleton(ChroniclerManager::class, DefaultChroniclerManager::class);
        $this->app->alias(ChroniclerManager::class, Chronicle::SERVICE_NAME);

        $this->app->singleton(RepositoryManager::class, DefaultRepositoryManager::class);

        $this->app->bind(JsonEncoder::class, config('chronicler.json_encoder'));
    }

    public function provides(): array
    {
        return [
            ChroniclerManager::class,
            Chronicle::SERVICE_NAME,
            RepositoryManager::class,
            JsonEncoder::class,
        ];
    }
}
