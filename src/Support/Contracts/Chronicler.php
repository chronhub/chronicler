<?php

namespace Chronhub\Chronicler\Support\Contracts;

use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Throwable;

interface Chronicler extends ReadOnlyChronicler
{
    /**
     * @param Stream $stream
     * @throws Throwable
     */
    public function persistFirstCommit(Stream $stream): void;

    /**
     * @param Stream $stream
     * @throws Throwable
     */
    public function persist(Stream $stream): void; // streamName, iterable

    /**
     * @param StreamName $streamName
     * @throws Throwable
     */
    public function delete(StreamName $streamName): void;
}
