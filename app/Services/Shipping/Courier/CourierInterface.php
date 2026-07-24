<?php

namespace App\Services\Shipping\Courier;

use App\Models\Order;

interface CourierInterface
{
    /**
     * Membuat shipment.
     */
    public function createShipment(
        Order $order
    ): array;

    /**
     * Update shipment.
     */
    public function updateShipment(
        Order $order
    ): array;

    /**
     * Cancel shipment.
     */
    public function cancelShipment(
        Order $order
    ): bool;

    /**
     * Tracking shipment.
     */
    public function tracking(
        Order $order
    ): array;
}