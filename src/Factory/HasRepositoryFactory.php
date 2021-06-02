<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Factory;

use Chronhub\Foundation\Support\Contracts\Message\MessageDecorator;
use function is_array;
use function array_map;
use function array_merge;

trait HasRepositoryFactory
{
    protected function determineStreamProducerDriver(array $config): ?string
    {
        $connection = $this->fromChronicler('connections.' . $config['chronicler']);

        if ('default' === $connection) {
            $connection = $this->fromChronicler('connections.default');
        }

        $strategy = is_array($connection)
            ? $connection['strategy']
            : $this->fromChronicler("connections.$connection.strategy");

        if ('default' === $strategy) {
            $strategy = $this->fromChronicler('strategy.default') ?? null;
        }

        return $this->fromChronicler("strategy.$strategy.producer");
    }

    /**
     * @return MessageDecorator[]
     */
    protected function determineRepositoryEventDecorator(string $streamName): array
    {
        $messageDecorators = [];

        if (true === $this->fromChronicler('use_foundation_decorators') ?? false) {
            $messageDecorators = $this->app['config']->get('reporter.messaging.decorators', []);
        }

        return array_map(
            fn (string $decorator) => $this->app->make($decorator),
            array_merge(
                $messageDecorators,
                $this->fromChronicler('event_decorators') ?? [],
                $this->fromChronicler("repositories.$streamName.event_decorators") ?? []
            )
        );
    }
}
