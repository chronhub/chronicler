<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Factory;

use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateRepository;

interface RepositoryManager
{
    public function create(string $streamName): AggregateRepository;

    public function extends(string $streamName, callable $repository): void;
}
