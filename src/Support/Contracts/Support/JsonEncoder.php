<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Support;

interface JsonEncoder
{
    public function encode(mixed $value): string;

    public function decode(string $json): mixed;
}
