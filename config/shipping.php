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

            'environment' => env(
                'JNT_CARGO_ENV',
                'sandbox'
            ),

            'base_urls' => [

                'sandbox' => env(
                    'JNT_CARGO_SANDBOX_BASE_URL',
                    'https://demoopenapi.jtcargo.co.id/webopenplatformapi/api'
                ),

                'production' => env(
                    'JNT_CARGO_PRODUCTION_BASE_URL',
                    'https://openapi.jtcargo.co.id/webopenplatformapi/api'
                ),

            ],

            'api_account' => env(
                'JNT_CARGO_API_ACCOUNT'
            ),

            'private_key' => env(
                'JNT_CARGO_PRIVATE_KEY'
            ),

            'customer_code' => env(
                'JNT_CARGO_CUSTOMER_CODE'
            ),

            'customer_password' => env(
                'JNT_CARGO_CUSTOMER_PASSWORD'
            ),

            'webhook_url' => env(
                'JNT_CARGO_WEBHOOK_URL'
            ),

            'timeout' => env(
                'JNT_CARGO_TIMEOUT',
                30
            ),

             /*
            |--------------------------------------------------------------------------
            | Static Sender
            |--------------------------------------------------------------------------
            |
            | Data pengirim/gudang Melody Furniture.
            | Tidak berasal dari Order maupun Customer.
            |
            */
            'sender' => [
                'name' => env(
                    'JNT_CARGO_SENDER_NAME',
                ),

                'mobile' => env(
                    'JNT_CARGO_SENDER_MOBILE'
                ),

                'country_code' => env(
                    'JNT_CARGO_SENDER_COUNTRY_CODE',
                ),

                'province' => env(
                    'JNT_CARGO_SENDER_PROVINCE'
                ),

                'area' => env(
                    'JNT_CARGO_SENDER_AREA'
                ),

                'city' => env(
                    'JNT_CARGO_SENDER_CITY'
                ),

                'address' => env(
                    'JNT_CARGO_SENDER_ADDRESS'
                ),

                'postcode' => env(
                    'JNT_CARGO_SENDER_POSTCODE'
                ),
            ],

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