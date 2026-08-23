<?php

namespace App\Services\Shipping\Courier;

use App\Models\Order;
use App\Services\Shipping\Courier\Clients\JntCargoClient;

class JntCargoService implements CourierInterface
{
    public function __construct(
        protected JntCargoClient $client,
    ) {}

    /**
     * Membuat shipment.
     */
    public function createShipment(
        Order $order
    ): CourierShipmentResult {

        /*
        |--------------------------------------------------------------------------
        | API integration
        |--------------------------------------------------------------------------
        |
        | Endpoint dan payload final akan disesuaikan setelah
        | dokumentasi resmi J&T Cargo tersedia.
        |
        */

        return CourierShipmentResult::failed(
            'Integrasi create shipment J&T Cargo belum dikonfigurasi.'
        );
    }

    /**
     * Update shipment.
     */
    public function updateShipment(
        Order $order
    ): CourierShipmentResult {

        return CourierShipmentResult::failed(
            'Integrasi update shipment J&T Cargo belum dikonfigurasi.'
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
        | J&T Cargo tersedia.
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
            'Integrasi tracking J&T Cargo belum dikonfigurasi.'
        );
    }
}