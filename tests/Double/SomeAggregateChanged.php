<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Double;

use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Foundation\Support\Contracts\Aggregate\AggregateId;

final class SomeAggregateChanged extends AggregateChanged
{
    public static function withData(AggregateId $aggregateId, array $payload): self
    {
        return self::occur($aggregateId->toString(), $payload);
    }
}
