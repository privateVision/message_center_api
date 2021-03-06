<?php

return [

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

    'default' => 'default',

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
        'default' => [
            'driver'    => 'mysql',
            'read' => [
                'host'      => env('DBR_HOST'),
                'port'      => env('DBR_PORT'),
                'database'  => env('DBR_DATABASE'),
                'username'  => env('DBR_USERNAME'),
                'password'  => env('DBR_PASSWORD'),
            ],
            'write' => [
                'host'      => env('DBW_HOST'),
                'port'      => env('DBW_PORT'),
                'database'  => env('DBW_DATABASE'),
                'username'  => env('DBW_USERNAME'),
                'password'  => env('DBW_PASSWORD'),
            ],
            'charset'   => env('DB_CHARSET', 'utf8'),
            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
            'prefix'    => env('DB_PREFIX'),
            'timezone'  => env('DB_TIMEZONE', '+00:00'),
            'strict'    => env('DB_STRICT_MODE', false),
        ],
        
        '56gamebbs' => [
            'driver'    => 'mysql',
            'read' => [
                'host'      => env('DBR_56GAMEBBS_HOST'),
                'port'      => env('DBR_56GAMEBBS_PORT'),
                'database'  => env('DBR_56GAMEBBS_DATABASE'),
                'username'  => env('DBR_56GAMEBBS_USERNAME'),
                'password'  => env('DBR_56GAMEBBS_PASSWORD'),
            ],
            'write' => [
                'host'      => env('DBW_56GAMEBBS_HOST'),
                'port'      => env('DBW_56GAMEBBS_PORT'),
                'database'  => env('DBW_56GAMEBBS_DATABASE'),
                'username'  => env('DBW_56GAMEBBS_USERNAME'),
                'password'  => env('DBW_56GAMEBBS_PASSWORD'),
            ],
            
            'charset'   => env('DB_56GAMEBBS_CHARSET', 'utf8'),
            'collation' => env('DB_56GAMEBBS_COLLATION', 'utf8_unicode_ci'),
            'prefix'    => env('DB_56GAMEBBS_PREFIX'),
            'timezone'  => env('DB_56GAMEBBS_TIMEZONE', '+00:00'),
            'strict'    => env('DB_56GAMEBBS_STRICT_MODE', false),
        ],
        
        'mongodb' => [
            'driver'   => 'mongodb',
            'host'     => env('MONGODB_HOST'),
            'port'     => env('MONGODB_PORT'),
            'database' => env('MONGODB_DATABASE'),
            'username' => env('MONGODB_USERNAME'),
            'password' => env('MONGODB_PASSWORD'),
            'options'  => [
                'database' => env('MONGODB_AUTH_DATABASE')// sets the authentication database required by mongo 3
            ]
        ],
        
        'log' => [
            'driver'   => 'mongodb',
            'host'     => env('MONGODB_LOG_HOST'),
            'port'     => env('MONGODB_LOG_PORT'),
            'database' => env('MONGODB_LOG_DATABASE'),
            'username' => env('MONGODB_LOG_USERNAME'),
            'password' => env('MONGODB_LOG_PASSWORD'),
            'options'  => [
                'database' => env('MONGODB_LOG_AUTH_DATABASE')// sets the authentication database required by mongo 3
            ]
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
            'prefix' => env('REDIS_PREFIX'),
        ],
    ],
];
