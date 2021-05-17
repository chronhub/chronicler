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

            $this->commands(config('chronicler.console.command', []));
        }
    }

    public function register(): void
    {
        foreach ($this->configKeys as $configKey) {
            $this->mergeConfigFrom($this->getConfigPath($configKey), 'chronicler');
        }
    }

    private function publishConfig(): void
    {
        foreach ($this->configKeys as $configKey) {
            $this->publishes(
                [$this->getConfigPath($configKey) => config_path($configKey . '.php')],
                'config'
            );
        }
    }

    private function getConfigPath(string $key): string
    {
        return __DIR__ . '/../../config/' . $key . '.php';
    }
}
