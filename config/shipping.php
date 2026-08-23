<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Courier Configuration
    |--------------------------------------------------------------------------
    */

    'couriers' => [

        'jnt_cargo' => [

            'name' => 'J&T Cargo',

            'base_url' => env(
                'JNT_CARGO_BASE_URL'
            ),

            'api_key' => env(
                'JNT_CARGO_API_KEY'
            ),

            'api_secret' => env(
                'JNT_CARGO_API_SECRET'
            ),

            /*
            |------------------------------------------------------------------
            | Webhook
            |------------------------------------------------------------------
            |
            | URL yang nantinya diberikan kepada pihak J&T Cargo.
            |
            */

            'webhook_url' => env(
                'JNT_CARGO_WEBHOOK_URL'
            ),

            'timeout' => env(
                'JNT_CARGO_TIMEOUT',
                30
            ),

        ],

        'sentral_cargo' => [

            'name' => 'Sentral Cargo',

            'base_url' => env(
                'SENTRAL_CARGO_BASE_URL'
            ),

            'api_key' => env(
                'SENTRAL_CARGO_API_KEY'
            ),

            'api_secret' => env(
                'SENTRAL_CARGO_API_SECRET'
            ),

            /*
            |------------------------------------------------------------------
            | Webhook
            |------------------------------------------------------------------
            */

            'webhook_url' => env(
                'SENTRAL_CARGO_WEBHOOK_URL'
            ),

            'timeout' => env(
                'SENTRAL_CARGO_TIMEOUT',
                30
            ),

        ],

    ],

];