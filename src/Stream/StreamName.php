<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Stream;

use Chronhub\Chronicler\Exception\InvalidArgumentException;

final class StreamName
{
    private string $name;

    public function __construct(string $name)
    {
        $name = trim($name);

        if ('' === $name) {
            throw new InvalidArgumentException('Invalid stream name');
        }

        $this->name = $name;
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
