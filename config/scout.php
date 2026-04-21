<?php

return [

    'driver' => env('SCOUT_DRIVER', 'typesense'),

    'prefix' => env('SCOUT_PREFIX', ''),

    'queue' => env('SCOUT_QUEUE', false),

    'after_commit' => false,

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    'soft_delete' => true,

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia
    |--------------------------------------------------------------------------
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
        'index-settings' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch
    |--------------------------------------------------------------------------
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [],
    ],

    //Typesense
   

 'typesense' => [

    'client-settings' => [
        'api_key' => env('TYPESENSE_API_KEY', 'xyz'),

        'nodes' => [
            [
                'host' => env('TYPESENSE_HOST', 'typesense'),
                'port' => env('TYPESENSE_PORT', '8108'),
                'path' => env('TYPESENSE_PATH', ''),
                'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
            ],
        ],

        'connection_timeout_seconds' => 2,
        'healthcheck_interval_seconds' => 30,
        'num_retries' => 3,
        'retry_interval_seconds' => 1,
    ],

    'model-settings' => [

      \App\Models\ProjectManuscript::class => [

                'collection-schema' => [
                    'name' => 'project_manuscripts',

                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'abstract', 'type' => 'string'],
                        ['name' => 'status', 'type' => 'string', 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],

                        // ✅ MANUAL VECTOR FIELD (OLLAMA)
                        [
                            'name' => 'embedding',
                            'type' => 'float[]',
                            'num_dim' => 768,
                        ],
                    ],

                    'default_sorting_field' => 'created_at',
                ],

                'search-parameters' => [
                    'query_by' => 'title,abstract',
                ],
            ],
        ],
    ],
];