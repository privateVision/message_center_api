<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_CLASS,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [
        'anfanapi' => [
            'driver'    => 'mysql',
            'host'      => env('DB_ANFANAPI_HOST'),
            'port'      => env('DB_ANFANAPI_PORT'),
            'database'  => env('DB_ANFANAPI_DATABASE'),
            'username'  => env('DB_ANFANAPI_USERNAME'),
            'password'  => env('DB_ANFANAPI_PASSWORD'),
            'charset'   => env('DB_ANFANAPI_CHARSET', 'utf8'),
            'collation' => env('DB_ANFANAPI_COLLATION', 'utf8_unicode_ci'),
            'prefix'    => env('DB_ANFANAPI_PREFIX'),
            'timezone'  => env('DB_ANFANAPI_TIMEZONE', '+00:00'),
            'strict'    => env('DB_ANFANAPI_STRICT_MODE', false),
        ],

        '56gamebbs' => [
            'driver'    => 'mysql',
            'host'      => env('DB_56GAMEBBS_HOST'),
            'port'      => env('DB_56GAMEBBS_PORT'),
            'database'  => env('DB_56GAMEBBS_DATABASE'),
            'username'  => env('DB_56GAMEBBS_USERNAME'),
            'password'  => env('DB_56GAMEBBS_PASSWORD'),
            'charset'   => env('DB_56GAMEBBS_CHARSET', 'utf8'),
            'collation' => env('DB_56GAMEBBS_COLLATION', 'utf8_unicode_ci'),
            'prefix'    => env('DB_56GAMEBBS_PREFIX'),
            'timezone'  => env('DB_56GAMEBBS_TIMEZONE', '+00:00'),
            'strict'    => env('DB_56GAMEBBS_STRICT_MODE', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'cluster' => env('REDIS_CLUSTER'),

        'default' => [
            'host'     => env('REDIS_HOST'),
            'port'     => env('REDIS_PORT'),
            'database' => env('REDIS_DATABASE'),
            'password' => env('REDIS_PASSWORD'),
        ],

    ],

];