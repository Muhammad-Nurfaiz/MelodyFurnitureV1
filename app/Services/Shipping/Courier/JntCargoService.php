<?php

namespace App\Services\Shipping\Courier;

use App\Models\Order;

class JntCargoService implements CourierInterface
{
    public function createShipment(
        Order $order
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Dummy
        |--------------------------------------------------------------------------
        */

        return [

            'booking_code'
                => 'JNT'.now()->format('YmdHis'),

            'tracking_number'
                => null,

            'status'
                => 'waiting_pickup',

        ];

    }

    public function updateShipment(
        Order $order
    ): array {

        return [];

    }

    public function cancelShipment(
        Order $order
    ): bool {

        return true;

    }

    public function tracking(
        Order $order
    ): array {

        return [];

    }
}