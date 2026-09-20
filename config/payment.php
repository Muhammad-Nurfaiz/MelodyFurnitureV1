<?php

return [
    'expired_minutes' => (int) env('PAYMENT_EXPIRED_MINUTES',10),
    'midtrans' => [
        'enabled_payments' => null,
    ],

];