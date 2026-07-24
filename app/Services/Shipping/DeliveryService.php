<?php

namespace App\Services\Shipping;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Services\Order\OrderWorkflowService;

class DeliveryService
{
    public function __construct(
        protected ShipmentService $shipmentService,
        protected OrderWorkflowService $workflowService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Pickup
    |--------------------------------------------------------------------------
    */

    public function pickup(
        Order $order,
        ?string $createdBy = 'admin'
    ): Order {

        $this->workflowService->validate(
            $order,
            'picked_up'
        );

        return DB::transaction(function () use (

            $order,
            $createdBy

        ) {

            $shipment = $order->shipment;

            $this->shipmentService
                ->markPickedUp(
                    $shipment
                );

            $this->workflowService
                ->changeStatus(

                    $order,

                    'picked_up',

                    'Barang dijemput ekspedisi.',

                    $createdBy

                );

            return $order->fresh([
                'shipment',
            ]);

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Transit
    |--------------------------------------------------------------------------
    */

    public function transit(
        Order $order
    ): Order {

        DB::transaction(function () use ($order) {

            $this->shipmentService
                ->markInTransit(
                    $order->shipment
                );

        });

        return $order->fresh([
            'shipment',
        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | Delivered
    |--------------------------------------------------------------------------
    */

    public function delivered(
        Order $order,
        ?string $createdBy = 'system'
    ): Order {

        $this->workflowService->validate(
            $order,
            'completed'
        );

        return DB::transaction(function () use (

            $order,
            $createdBy

        ) {

            $this->shipmentService
                ->markDelivered(
                    $order->shipment
                );

            $this->workflowService
                ->changeStatus(

                    $order,

                    'completed',

                    'Pesanan telah diterima pelanggan.',

                    $createdBy

                );

            return $order->fresh([
                'shipment',
            ]);

        });

    }
}