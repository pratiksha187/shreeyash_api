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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'log'),
    ],

    'whatsapp_web' => [
        'endpoint' => env('WHATSAPP_WEB_ENDPOINT', 'http://127.0.0.1:3010/send-message'),
        'token' => env('WHATSAPP_WEB_TOKEN'),
    ],

    'gupshup' => [
        'endpoint' => env('GUPSHUP_ENDPOINT', 'https://api.gupshup.io/wa/api/v1/msg'),
        'api_key' => env('GUPSHUP_API_KEY'),
        'source_number' => env('GUPSHUP_SOURCE_NUMBER'),
        'app_name' => env('GUPSHUP_APP_NAME'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'verify_service_sid' => env('TWILIO_VERIFY_SERVICE_SID'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
