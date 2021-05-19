<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Factory;

use Chronhub\Chronicler\Support\Contracts\Chronicler;

interface ChroniclerManager
{
    public function create(string $name = 'default'): Chronicler;

    public function extends(string $name, callable $chronicler): void;
}
