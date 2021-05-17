<?php

namespace Chronhub\Chronicler\Support\Contracts;

use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Message\DomainEvent;

interface StreamPersistence
{
    /**
     * @param StreamName $streamName
     * @return string
     */
    public function tableName(StreamName $streamName): string;

    /**
     * @param string $tableName
     * @return callable|null
     */
    public function up(string $tableName): ?callable;

    /**
     * @param DomainEvent $event
     * @return array
     */
    public function serializeMessage(DomainEvent $event): array;

    /**
     * @return bool
     */
    public function isOneStreamPerAggregate(): bool;
}
