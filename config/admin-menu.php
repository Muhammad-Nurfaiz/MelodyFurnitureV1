<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    [
        'header' => null,

        'items' => [

            [
                'title' => 'Dashboard',
                'route' => 'admin.dashboard',
                'icon'  => 'home',

                'active' => [
                    'admin.dashboard',
                ],
            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Operasional
    |--------------------------------------------------------------------------
    */

    [
        'header' => 'OPERASIONAL',

        'items' => [

            [
                'title' => 'Manajemen Pesanan',
                'route' => 'admin.orders.index',
                'icon'  => 'shopping-bag',

                'active' => [
                    'admin.orders.*',
                ],
            ],

            [
                'title' => 'Katalog Produk',
                'route' => 'admin.products.index',
                'icon'  => 'cube',

                'active' => [
                    'admin.products.*',
                ],
            ],

            [
                'title' => 'Kategori',
                'route' => 'admin.categories.index',
                'icon'  => 'squares-2x2',

                'active' => [
                    'admin.categories.*',
                ],
            ],

            [
                'title' => 'Series',
                'route' => 'admin.series.index',
                'icon'  => 'tag',

                'active' => [
                    'admin.series.*',
                ],
            ],

            [
                'title' => 'Customer',
                'route' => 'admin.customers.index',
                'icon'  => 'users',

                'active' => [
                    'admin.customers.*',
                ],
            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Marketing
    |--------------------------------------------------------------------------
    */

    [
        'header' => 'MARKETING',

        'items' => [

            [
                'title' => 'Voucher',
                'route' => 'admin.vouchers.index',
                'icon'  => 'ticket',

                'active' => [
                    'admin.vouchers.*',
                ],
            ],

            [
                'title' => 'WhatsApp Automation',
                'route' => 'admin.whatsapp.index',
                'icon'  => 'chat-bubble-left-right',

                'active' => [
                    'admin.whatsapp.*',
                ],
            ],

            [
                'title' => 'Tarif Shipping',
                'route' => 'admin.shipping-rates.index',
                'icon'  => 'truck',

                'active' => [
                    'admin.shipping-rates.*',
                ],
            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Pengaturan
    |--------------------------------------------------------------------------
    */

    // [
    //     'header' => 'PENGATURAN',

    //     'items' => [

    //         [
    //             'title' => 'Admin',
    //             'route' => 'admin.admins.index',
    //             'icon'  => 'shield-check',

    //             'active' => [
    //                 'admin.admins.*',
    //             ],
    //         ],

    //         [
    //             'title' => 'Profil',
    //             'route' => 'profile.edit',
    //             'icon'  => 'user-circle',

    //             'active' => [
    //                 'profile.*',
    //             ],
    //         ],

    //     ],

    // ],

];