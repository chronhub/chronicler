<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Traits;

trait DetectStreamCategory
{
    public function detectStreamCategory(string $streamName, string $needle = '-'): ?string
    {
        $pos = strpos($streamName, $needle);

        return (false !== $pos && $pos > 0) ? substr($streamName, 0, $pos) : null;
    }
}
