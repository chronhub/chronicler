<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Factory;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use function is_array;
use function is_numeric;

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

            $this->commands(config('chronicler.console.commands', []));
        }
    }

    public function register(): void
    {
        $config = $this->mergeConfigs(
            $this->app['config']->get('chronicler'),
            $this->app['config']->get('repositories'),
        );

        $this->app['config']->set('chronicler', $config);
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

    private function mergeConfigs(array $original, array $merging)
    {
        $array = array_merge($original, $merging);

        foreach ($original as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if (!Arr::exists($merging, $key)) {
                continue;
            }

            if (is_numeric($key)) {
                continue;
            }

            $array[$key] = $this->mergeConfigs($value, $merging[$key]);
        }

        return $array;
    }

    private function getConfigPath(string $key): string
    {
        return __DIR__ . '/../../config/' . $key . '.php';
    }
}
