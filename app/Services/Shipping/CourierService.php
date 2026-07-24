<?php

namespace App\Services\Shipping;

use App\Models\Order;
use RuntimeException;
use App\Services\Shipping\Courier\CourierInterface;
use App\Services\Shipping\Courier\JntCargoService;
use App\Services\Shipping\Courier\SentralCargoService;

class CourierService
{
    public function __construct(
        protected JntCargoService $jntCargoService,
        protected SentralCargoService $sentralCargoService,
    ) {}

    /**
     * Resolve courier.
     */
    protected function resolve(
        string $courier
    ): CourierInterface {

        return match ($courier) {

            'jnt_cargo'
                => $this->jntCargoService,

            'sentral_cargo'
                => $this->sentralCargoService,

            default
                => throw new RuntimeException(
                    "Courier {$courier} tidak didukung."
                ),

        };

    }

    /**
     * Create shipment.
     */
    public function createShipment(
        Order $order
    ): array {

        return $this->resolve(
            $order->courier
        )->createShipment($order);

    }

    /**
     * Update shipment.
     */
    public function updateShipment(
        Order $order
    ): array {

        return $this->resolve(
            $order->courier
        )->updateShipment($order);

    }

    /**
     * Cancel shipment.
     */
    public function cancelShipment(
        Order $order
    ): bool {

        return $this->resolve(
            $order->courier
        )->cancelShipment($order);

    }

    /**
     * Tracking.
     */
    public function tracking(
        Order $order
    ): array {

        return $this->resolve(
            $order->courier
        )->tracking($order);

    }
}