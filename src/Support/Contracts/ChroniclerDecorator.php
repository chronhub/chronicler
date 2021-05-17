<?php

namespace Chronhub\Chronicler\Support\Contracts;

interface ChroniclerDecorator extends Chronicler
{
    /**
     * @return Chronicler
     */
    public function innerChronicler(): Chronicler;
}
