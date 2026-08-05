<?php

return [
    'expired_minutes' => (int) env('PAYMENT_EXPIRED_MINUTES',30),
    'midtrans' => [
        'enabled_payments' => [
            'bank_transfer',
            'gopay',
            'qris',
        ],
    ],

];