<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts;

use Throwable;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;

interface Chronicler extends ReadOnlyChronicler
{
    /**
     * @throws Throwable
     */
    public function persistFirstCommit(Stream $stream): void;

    /**
     * @throws Throwable
     */
    public function persist(Stream $stream): void; // streamName, iterable

    /**
     * @throws Throwable
     */
    public function delete(StreamName $streamName): void;
}
