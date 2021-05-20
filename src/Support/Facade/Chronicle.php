<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @method static create(string $name = 'default')
 * @method static extends (string $name, callable $chronicler)
 */
final class Chronicle extends Facade
{
    const SERVICE_NAME = 'chronicler.manager';

    protected static function getFacadeAccessor(): string
    {
        return self::SERVICE_NAME;
    }
}
