<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Functional\Factory;

use Chronhub\Chronicler\Tests\TestCaseWithOrchestra;
use Chronhub\Foundation\Reporter\Services\ConfigurationServiceProvider;

final class ConfigurationServiceProviderTest extends TestCaseWithOrchestra
{
    protected function getPackageProviders($app): array
    {
        return [ConfigurationServiceProvider::class];
    }
}
