<?php

namespace Chronhub\Chronicler\Support\Contracts\Model;

interface EventStreamModel
{
    public const TABLE = 'event_streams';

    /**
     * @return string
     */
    public function realStreamName(): string;

    /**
     * @return string
     */
    public function tableName(): string;

    /**
     * @return string|null
     */
    public function category(): ?string;
}
