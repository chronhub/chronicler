<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts;

interface ChroniclerDecorator extends Chronicler
{
    public function innerChronicler(): Chronicler;
}
