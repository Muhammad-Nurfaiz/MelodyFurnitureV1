<?php

namespace App\Services\Shipping\Courier;

use App\Models\Order;

interface CourierInterface
{
    /**
     * Membuat shipment pada ekspedisi.
     */
    public function createShipment(
        Order $order
    ): CourierShipmentResult;

    /**
     * Update shipment pada ekspedisi.
     */
    public function updateShipment(
        Order $order
    ): CourierShipmentResult;

    /**
     * Membatalkan shipment pada ekspedisi.
     */
    public function cancelShipment(
        Order $order
    ): bool;

    /**
     * Tracking shipment pada ekspedisi.
     */
    public function tracking(
        Order $order
    ): CourierShipmentResult;
}