<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
    
    'cloudveil' => [
        'host' => env('WORDPRESS_HOST'),
        'api_me' => env('WORDPRESS_API_ME', env('WORDPRESS_HOST') . '/me/'),
    
        'client_id' => env('WORDPRESS_CLIENT_ID'),
        'client_secret' => env('WORDPRESS_CLIENT_SECRET'),
        'redirect' => env('WORDPRESS_REDIRECT')
    ],
    'slack' => [
            'url' => env('SLACK_URL'),
            'channel' => [
                    'import' => env('SLACK_CHANNEL_IMPORTS'),
            ]
    ],
];
