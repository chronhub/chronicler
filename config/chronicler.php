<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Json encoder
    |--------------------------------------------------------------------------
    |
    */

    'json_encoder' => \Chronhub\Chronicler\Support\EncodeJson::class,

    /*
    |--------------------------------------------------------------------------
    | Event stream provider
    |--------------------------------------------------------------------------
    |
    | Must be registered in ioc
    */

    'provider' => [
        'eloquent'  => \Chronhub\Chronicler\Driver\Connection\EventStream::class,
        'in_memory' => \Chronhub\Chronicler\Driver\InMemory\InMemoryEventStream::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Event store persistence strategy
    |--------------------------------------------------------------------------
    |
    | Persistence strategy wil be used by the chronicler
    | Producer strategy wil be used by the aggregate repository
    |
    */

    'strategy' => [
        'default' => 'single',

        /*
         * One stream per aggregate type
         * each aggregate per aggregate id will have his own event store/table
         * eg: account-1234-5678-9101 ...
         */
        'aggregate' => [
            'persistence' => \Chronhub\Chronicler\Driver\Connection\Persistence\PgsqlAggregateStreamPersistence::class,
            'producer'    => \Chronhub\Chronicler\Producer\OneStreamPerAggregate::class,
        ],

        /*
         * Single stream per aggregate
         * each aggregate root would have his his own event store/table
         *
         * require pessimistic lock
         */
        'single' => [
            'persistence' => \Chronhub\Chronicler\Driver\Connection\Persistence\PgsqlSingleStreamPersistence::class,
            'producer'    => \Chronhub\Chronicler\Producer\SingleStreamPerAggregate::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event store connection
    |--------------------------------------------------------------------------
    |
    | Gap detection
    |
    | write lock strategy is mandatory for a single strategy to prevent missing events
    | but event if, with a lock, false positive appears due to rollback transaction
    | and auto increment visibility
    | note: the pgsql use advisory lock and required to be under transaction
    |
    */

    'connections' => [
        'default' => 'pgsql',

        'pgsql' => [
            'driver' => 'pgsql',

            'tracking' => [
                'tracker_id'  => \Chronhub\Chronicler\Tracking\TrackTransactionalStream::class,
                'subscribers' => [
                    \Chronhub\Chronicler\Tracking\Subscribers\PublishEvents::class,
                ],
            ],

            'options' => [
                'write_lock'          => true,
                'use_event_decorator' => true,
            ],

            'scope'        => \Chronhub\Chronicler\Driver\Connection\Scope\PgsqlQueryScope::class,
            'strategy'     => 'default',
            'provider'     => 'eloquent',
            'query_loader' => \Chronhub\Chronicler\Driver\Connection\Loader\LazyQueryLoader::class,
        ],

        'projecting' => [
            'driver' => 'pgsql',

            'tracking' => [
                'tracker_id' => \Chronhub\Chronicler\Tracking\TrackStream::class,
            ],

            'options' => [
                'write_lock'          => false,
                'use_event_decorator' => false,
            ],

            'scope'        => \Chronhub\Chronicler\Driver\Connection\Scope\PgsqlQueryScope::class,
            'strategy'     => 'default',
            'provider'     => 'eloquent',
            'query_loader' => \Chronhub\Chronicler\Driver\Connection\Loader\LazyQueryLoader::class,
        ],

        'in_memory' => [
            'driver'   => 'in_memory',
            'strategy' => 'single',
            'provider' => 'in_memory',
            'options'  => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration and command
    |--------------------------------------------------------------------------
    |
    */
    'console'     => [
        'load_migrations' => true,

        'commands' => [
            \Chronhub\Chronicler\Support\Console\CreateEventStreamCommand::class,
        ],
    ],
];
