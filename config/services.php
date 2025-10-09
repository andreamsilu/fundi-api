<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    /*
    |--------------------------------------------------------------------------
    | ZenoPay Payment Gateway (Tanzania Mobile Money)
    |--------------------------------------------------------------------------
    | 
    | ZenoPay handles M-Pesa, Tigo Pesa, and Airtel Money payments in Tanzania
    | Get your API key from: https://zenoapi.com
    | Documentation: https://zenopay-docs.netlify.app
    |
    */
    'zenopay' => [
        'api_key' => env('ZENOPAY_API_KEY'),
        'base_url' => env('ZENOPAY_BASE_URL', 'https://zenoapi.com'),
        'webhook_url' => env('ZENOPAY_WEBHOOK_URL', env('APP_URL') . '/api/payments/zenopay/webhook'),
        'enabled' => env('ZENOPAY_ENABLED', true),
    ],

];
