<?php

namespace App\Services\Shipping;

use App\Models\Admin;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeliveryService
{
    public function __construct(
        protected ShipmentService $shipmentService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Pickup
    |--------------------------------------------------------------------------
    */

    /**
     * Mark shipment as picked up by courier.
     *
     * Flow:
     *
     * Shipment:
     * waiting_pickup
     *      ↓
     * picked_up
     *
     * Order:
     * processing
     *      ↓
     * picked_up
     */
    public function pickup(
        Order $order,
        ?string $createdBy = 'admin'
    ): Order {

        $shipment = $order->shipment;

        if (! $shipment) {
            throw new RuntimeException(
                'Order belum memiliki shipment.'
            );
        }

        return DB::transaction(function () use (
            $shipment,
            $createdBy
        ) {

            $admin = $this->resolveAdmin(
                $createdBy
            );

            $this->shipmentService->markPickedUp(
                $shipment,
                $admin
            );

            return $shipment->order->fresh([
                'shipment',
                'payment',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Transit
    |--------------------------------------------------------------------------
    */

    /**
     * Mark shipment as in transit.
     *
     * Flow:
     *
     * Shipment:
     * picked_up
     *      ↓
     * in_transit
     *
     * Order:
     * picked_up
     *      ↓
     * shipped
     */
    public function transit(
        Order $order,
        ?string $createdBy = 'system'
    ): Order {

        $shipment = $order->shipment;

        if (! $shipment) {
            throw new RuntimeException(
                'Order belum memiliki shipment.'
            );
        }

        return DB::transaction(function () use (
            $shipment,
            $createdBy
        ) {

            $admin = $this->resolveAdmin(
                $createdBy
            );

            $this->shipmentService->markInTransit(
                $shipment,
                $admin
            );

            return $shipment->order->fresh([
                'shipment',
                'payment',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Delivered
    |--------------------------------------------------------------------------
    */

    /**
     * Mark shipment as delivered.
     *
     * Flow:
     *
     * Shipment:
     * in_transit
     *      ↓
     * delivered
     *
     * Order:
     * shipped
     *      ↓
     * completed
     */
    public function delivered(
        Order $order,
        ?string $createdBy = 'system'
    ): Order {

        $shipment = $order->shipment;

        if (! $shipment) {
            throw new RuntimeException(
                'Order belum memiliki shipment.'
            );
        }

        return DB::transaction(function () use (
            $shipment,
            $createdBy
        ) {

            $admin = $this->resolveAdmin(
                $createdBy
            );

            $this->shipmentService->markDelivered(
                $shipment,
                $admin
            );

            return $shipment->order->fresh([
                'shipment',
                'payment',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Admin
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve actor menjadi Admin.
     *
     * ShipmentService membutuhkan object Admin,
     * sedangkan DeliveryService menerima ID/string actor.
     */
    private function resolveAdmin(
        ?string $createdBy
    ): Admin {

        if (blank($createdBy)) {
            throw new RuntimeException(
                'Admin tidak ditemukan.'
            );
        }

        /*
        |----------------------------------------------------------------------
        | System Actor
        |----------------------------------------------------------------------
        |
        | Untuk sementara proses otomatis/system belum menggunakan
        | DeliveryService karena ShipmentService::mark* membutuhkan Admin.
        |
        */

        if ($createdBy === 'system') {
            throw new RuntimeException(
                'DeliveryService membutuhkan Admin untuk proses shipment.'
            );
        }

        $admin = Admin::find(
            $createdBy
        );

        if (! $admin) {
            throw new RuntimeException(
                'Admin yang melakukan proses shipment tidak ditemukan.'
            );
        }

        return $admin;
    }
}