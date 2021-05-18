<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Factory;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class ConfigurationServiceProvider extends ServiceProvider
{
    private array $configKeys = ['chronicler', 'repositories'];

    /**
     * @var Application
     */
    public $app;

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
        $this->mergeConfigFrom(config_path('chronicler.php'), 'chronicler');
        $this->mergeConfigFrom(config_path('repositories.php'), 'chronicler');
    }

    private function publishConfig(): void
    {
        foreach ($this->configKeys as $configKey) {
            $this->publishes(
                [$this->getConfigPath($configKey) => config_path($configKey . '.php')],
                'chronicler'
            );
        }
    }

    private function getConfigPath(string $key): string
    {
        return __DIR__ . '/../../config/' . $key . '.php';
    }
}
