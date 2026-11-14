<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paymongo' => [
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'api_url' => 'https://api.paymongo.com/v2',
    ],

    'payments' => [
        'demo_mode' => env('SUBSCRIPTION_DEMO_MODE', env('APP_ENV') === 'local'),
        'vercel_environment' => env('VERCEL_ENV'),
    ],

    'location' => [
        'tile_url' => env('LOCATION_MAP_TILES_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'reverse_geocode_url' => env('LOCATION_REVERSE_GEOCODE_URL', 'https://nominatim.openstreetmap.org/reverse'),
    ],

];
