<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support;

use Chronhub\Chronicler\Exception\RuntimeException;
use Chronhub\Chronicler\Support\Contracts\Support\JsonEncoder;

final class EncodeJson implements JsonEncoder
{
    public function encode(mixed $value): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

        $json = json_encode($value, $flags);

        if (JSON_ERROR_NONE !== $error = json_last_error()) {
            throw new RuntimeException(json_last_error_msg(), $error);
        }

        return $json;
    }

    public function decode(string $json): mixed
    {
        $data = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);

        if (JSON_ERROR_NONE !== $error = json_last_error()) {
            throw new RuntimeException(json_last_error_msg(), $error);
        }

        return $data;
    }
}
