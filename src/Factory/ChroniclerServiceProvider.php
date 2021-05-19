<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Factory;

use Illuminate\Support\AggregateServiceProvider;

final class ChroniclerServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        ConfigurationServiceProvider::class,
        EventStoreServiceProvider::class,
    ];
}
