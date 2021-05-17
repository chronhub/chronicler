<?php

namespace Chronhub\Chronicler\Support\Contracts\Factory;

use Chronhub\Chronicler\Support\Contracts\Chronicler;

interface ChroniclerManager
{
    /**
     * @param string $name
     * @return Chronicler
     */
    public function create(string $name = 'default'): Chronicler;

    /**
     * @param string   $name
     * @param callable $chronicler
     */
    public function extends(string $name, callable $chronicler): void;
}
