<?php

namespace App\Services\Shipping\Courier;

use App\Models\Order;

class SentralCargoService implements CourierInterface
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
                => null,

            'tracking_number'
                => 'SC'.now()->format('YmdHis'),

            'status'
                => 'ready_to_print',

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