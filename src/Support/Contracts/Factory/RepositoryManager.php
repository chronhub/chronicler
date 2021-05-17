<?php

namespace Chronhub\Chronicler\Support\Contracts\Factory;

use Chronhub\Chronicler\Support\Contracts\Aggregate\AggregateRepository;

interface RepositoryManager
{
    /**
     * @param string $streamName
     * @return AggregateRepository
     */
    public function create(string $streamName): AggregateRepository;

    /**
     * @param string   $streamName
     * @param callable $repository
     */
    public function extends(string $streamName, callable $repository): void;
}
