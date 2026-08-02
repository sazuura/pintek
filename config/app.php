<?php

use Illuminate\Support\Facades\Facade;

return [

    'name' => env('APP_NAME', 'Diskominfotik | Penjadwalan Operator'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    'timezone' => 'Asia/Jakarta',

    'locale' => env('APP_LOCALE', 'id'),

    'fallback_locale' => 'en',

    'faker_locale' => 'en_US',

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',

    ],

    'aliases' => Facade::defaultAliases()->merge([

    ])->toArray(),

];
