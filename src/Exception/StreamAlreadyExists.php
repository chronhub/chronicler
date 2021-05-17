<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Exception;

use Chronhub\Chronicler\Stream\StreamName;

class StreamAlreadyExists extends RuntimeException
{
    public static function withStreamName(StreamName $streamName): self
    {
        return new self("Stream name {$streamName->toString()} already exists");
    }
}
