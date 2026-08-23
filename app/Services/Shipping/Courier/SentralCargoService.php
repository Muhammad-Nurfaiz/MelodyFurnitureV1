<?php

namespace App\Services\Shipping\Courier;

use App\Models\Order;
use App\Services\Shipping\Courier\Clients\SentralCargoClient;

class SentralCargoService implements CourierInterface
{
    public function __construct(
        protected SentralCargoClient $client,
    ) {}

    /**
     * Membuat shipment.
     */
    public function createShipment(
        Order $order
    ): CourierShipmentResult {

        return CourierShipmentResult::failed(
            'Integrasi create shipment Sentral Cargo belum dikonfigurasi.'
        );
    }

    /**
     * Update shipment.
     */
    public function updateShipment(
        Order $order
    ): CourierShipmentResult {

        return CourierShipmentResult::failed(
            'Integrasi update shipment Sentral Cargo belum dikonfigurasi.'
        );
    }

    /**
     * Cancel shipment.
     */
    public function cancelShipment(
        Order $order
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | API integration
        |--------------------------------------------------------------------------
        |
        | Endpoint cancel belum diketahui sampai dokumentasi resmi
        | Sentral Cargo tersedia.
        |
        */

        return false;
    }

    /**
     * Tracking shipment.
     */
    public function tracking(
        Order $order
    ): CourierShipmentResult {

        return CourierShipmentResult::failed(
            'Integrasi tracking Sentral Cargo belum dikonfigurasi.'
        );
    }
}