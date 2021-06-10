<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Event Decorators
    |--------------------------------------------------------------------------
    |
    */

    'use_foundation_decorators' => true,

    /*
    |--------------------------------------------------------------------------
    | Event Decorators
    |--------------------------------------------------------------------------
    |
    | Decorate domain event/ aggregate changed for each repository
    |
    */

    'event_decorators' => [],

    /*
    |--------------------------------------------------------------------------
    | Aggregate Repository
    |--------------------------------------------------------------------------
    |
    | Each aggregate repository is defined by his stream name
    |
    */

    'repositories' => [
        /*
         * Stream name
         *
         */
        'my_stream_name' => [
            /*
             * Specify your aggregate root class as string or
             * an array with your aggregate root class with his subclasses
             */
            'aggregate_type' => [
                'root' => 'AG class name',
                'children' => [],
            ],

            /*
             * Chronicler connection key
             */
            'chronicler' => 'default',

            /*
             * Laravel cache config key
             *
             * determine aggregate cache key under cache tag
             * if not provided a tag will be generated as
             * {identity-aggregate id snake base class}
             *
             * size 0 to disable
             */
            'cache' => [
                'size' => 10000,
                'tag' => 'identity-my_account',
            ],

            /*
             * Aggregate Event decorators
             * merge with event decorators above
             */
            'event_decorators' => [],

            /*
             * Aggregate snapshot
             *
             */
            'snapshot' => [
                /*
                 * Enable snapshot
                 */
                'use_snapshot' => false,

                /*
                 * Snapshot stream name
                 * determine your own snapshot stream name or default: my_stream_name_snapshot
                 */
                'stream_name' => null,

                /*
                 * Snapshot store service
                 * must be a service registered in ioc
                 * @see '\Chronhub\Contracts\Snapshotting\SnapshotStore'
                 */
                'store' => 'snapshot.store.service.id',

                /*
                 * Snapshot Aggregate Repository
                 */
                'repository' => \Chronhub\Snapshot\Aggregate\AggregateSnapshotRepository::class,

                /*
                 * Persist snapshot every x events
                 *
                 * must be greater than 1
                 */
                'persist_every_x_events' => 1000,

                /*
                 * Snapshot projector
                 * name and options are defined in the projector configuration
                 */
                'projector' => [
                    'name' => 'default',
                    'options' => 'default',
                ],
            ],
        ],
    ],
];
