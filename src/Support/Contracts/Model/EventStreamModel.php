<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Contracts\Model;

interface EventStreamModel
{
    public const TABLE = 'event_streams';

    public function realStreamName(): string;

    public function tableName(): string;

    public function category(): ?string;
}
