<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Factory;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use function is_array;
use function is_numeric;

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
        $this->mergeConfigFrom($this->chroniclerPath, 'chronicler');
        $this->mergeConfigFrom($this->repositoryPath, 'chronicler');
    }

    private function publishConfig(): void
    {
        $this->publishes([
            $this->chroniclerPath => config_path('chronicler.php'),
            $this->repositoryPath => config_path('repositories.php'),
        ]);
    }

    protected function mergeConfigFrom($path, $key)
    {
        if (!($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $config = $this->app['config']->get($key, []);

            $this->app['config']->set($key, $this->mergeConfig(require $path, $config));
        }
    }

    //@see https://gist.github.com/koenhoeijmakers/0a8e326ee3b12a826d73be38693fb647
    private function mergeConfig(array $original, array $merging): array
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

            $array[$key] = $this->mergeConfig($value, $merging[$key]);
        }

        return $array;
    }
}
