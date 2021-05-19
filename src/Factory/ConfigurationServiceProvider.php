<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Factory;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

final class ConfigurationServiceProvider extends ServiceProvider
{
    /**
     * @var Application
     */
    public $app;

    protected string $chroniclerPath = __DIR__ . '/../../config/chronicler.php';
    protected string $repositoryPath = __DIR__ . '/../../config/repositories.php';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishConfig();

            if (true === config('chronicler.console.load_migrations') ?? false) {
                $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            }

            $this->commands(config('chronicler.console.commands', []));
        }
    }

    public function register(): void
    {
        $packageConfig = array_merge(
            require $this->chroniclerPath,
            require $this->repositoryPath,
        );

        $chronicler = $this->app['config']->get('chronicler', []);
        $repositories = $this->app['config']->get('repositories', []);

        $this->app['config']->set('chronicler', array_merge($packageConfig, $chronicler, $repositories));
    }

    private function publishConfig(): void
    {
        $this->publishes([$this->repositoryPath => config_path('repositories.php')]);
        $this->publishes([$this->chroniclerPath => config_path('chronicler.php')]);
    }
}
