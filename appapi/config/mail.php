<?php
return [
    'driver' => "smtp",
    'host' => env('MAIL_SMTP_HOST'),
    'port' => env('MAIL_SMTP_PORT'),
    'encryption' => env('MAIL_ENCRYPT'),
    'username' => env('MAIL_USERNAME'),
    'password' => env('MAIL_PASSWORD'),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS'),
        'name' => env('MAIL_FROM_NAME'),
    ],
];