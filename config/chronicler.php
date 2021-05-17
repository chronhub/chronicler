<?php

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
        'eloquent'  => '\Chronhub\Chronicler\Connection\Model\EventStream',
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

        'aggregate' => [
            'persistence' => '\Chronhub\Chronicler\Connection\Persistence\PgsqlAggregateStreamPersistence',
            'producer'    => \Chronhub\Chronicler\Producer\OneStreamPerAggregate::class,
        ],

        'single' => [
            'persistence' => '\Chronhub\Chronicler\Connection\Persistence\PgsqlSingleStreamPersistence',
            'producer'    => \Chronhub\Chronicler\Producer\SingleStreamPerAggregate::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event store connection
    |--------------------------------------------------------------------------
    |
    | todo handle strategy connection as service (todo in repo too)
    */

    'connections' => [

        'default' => 'pgsql',

        'pgsql' => [
            'driver' => 'pgsql',

            'tracking' => [
                'tracker_id'  => \Chronhub\Chronicler\Tracking\TrackTransactionalStream::class,
                'subscribers' => [
                    \Chronhub\Chronicler\Tracking\Subscribers\PublishEvents::class,
                ]
            ],

            'options' => [
                'write_lock'          => true,
                'use_event_decorator' => true
            ],

            'scope'    => '\Chronhub\Chronicler\Driver\Connection\Pgsql\PgsqlConnectionQueryScope::class',
            'strategy' => 'default',
            'provider' => 'eloquent',
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
        ]
    ]
];
