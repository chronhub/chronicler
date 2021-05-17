<?php

namespace Chronhub\Chronicler\Support\Contracts\Support;

interface JsonEncoder
{
    /**
     * @param mixed $value
     * @return string
     */
    public function encode(mixed $value): string;

    /**
     * @param string $json
     * @return mixed
     */
    public function decode(string $json): mixed;
}
