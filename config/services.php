<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'nocaptcha' => [
    'sitekey' => env('NOCAPTCHA_SITEKEY'),
    'secret' => env('NOCAPTCHA_SECRET'),
    ],

    'zoom' => [
        'akun_1' => [
            'account_id'    => env('ZOOM_AKUN1_ACCOUNT_ID', ''),
            'client_id'     => env('ZOOM_AKUN1_CLIENT_ID', ''),
            'client_secret' => env('ZOOM_AKUN1_CLIENT_SECRET', ''),
            'user_id'       => env('ZOOM_AKUN1_USER_ID', 'me'),
        ],
        'akun_2' => [
            'account_id'    => env('ZOOM_AKUN2_ACCOUNT_ID', ''),
            'client_id'     => env('ZOOM_AKUN2_CLIENT_ID', ''),
            'client_secret' => env('ZOOM_AKUN2_CLIENT_SECRET', ''),
            'user_id'       => env('ZOOM_AKUN2_USER_ID', 'me'),
        ],
    ],
];
