<?php

namespace App\Services\Order;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Services\Shipping\CourierService;
use App\Services\Shipping\ShipmentService;

class OrderFulfillmentService
{
    public function __construct(
        protected CourierService $courierService,
        protected ShipmentService $shipmentService,
        protected OrderWorkflowService $workflowService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Start Fulfillment
    |--------------------------------------------------------------------------
    */

    public function start(
        Order $order,
        ?string $createdBy = 'admin'
    ): Order {

        $this->workflowService->validate(
            $order,
            'processing'
        );

        return DB::transaction(function () use (

            $order,
            $createdBy

        ) {

            /*
            |--------------------------------------------------------------------------
            | Create Shipment
            |--------------------------------------------------------------------------
            */

            $shipment =

                $this->courierService
                    ->createShipment($order);

            /*
            |--------------------------------------------------------------------------
            | Save Shipment
            |--------------------------------------------------------------------------
            */

            $this->shipmentService
                ->create(
                    $order,
                    $shipment
                );

            /*
            |--------------------------------------------------------------------------
            | Change Status
            |--------------------------------------------------------------------------
            */

            $this->workflowService
                ->changeStatus(

                    $order,

                    'processing',

                    'Pesanan mulai diproses.',

                    $createdBy

                );

            return $order->fresh([

                'shipment',

                'payment',

            ]);

        });

    }
}