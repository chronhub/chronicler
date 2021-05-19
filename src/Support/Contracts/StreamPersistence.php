<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts;

use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Foundation\Message\DomainEvent;

interface StreamPersistence
{
    public function tableName(StreamName $streamName): string;

    public function up(string $tableName): ?callable;

    public function serializeMessage(DomainEvent $event): array;

    public function isOneStreamPerAggregate(): bool;
}
