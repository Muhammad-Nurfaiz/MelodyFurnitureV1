<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\Shipment;

class ShipmentService
{
    /*
    |--------------------------------------------------------------------------
    | Create Shipment
    |--------------------------------------------------------------------------
    */

    public function create(
        Order $order,
        array $shipment
    ): Shipment {

        return Shipment::create([

            'order_id'
                => $order->id,

            'courier'
                => $order->courier,

            'service'
                => $order->shipping_service,

            'booking_code'
                => $shipment['booking_code'] ?? null,

            'tracking_number'
                => $shipment['tracking_number'] ?? null,

            'label_url'
                => $shipment['label_url'] ?? null,

            'status'
                => $shipment['status'],

            'metadata'
                => $shipment['metadata'] ?? null,

        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | Update Shipment
    |--------------------------------------------------------------------------
    */

    public function update(
        Shipment $shipment,
        array $data
    ): Shipment {

        $shipment->update($data);

        return $shipment->fresh();

    }

    /*
    |--------------------------------------------------------------------------
    | Set Tracking Number
    |--------------------------------------------------------------------------
    */

    public function setTrackingNumber(
        Shipment $shipment,
        string $trackingNumber
    ): Shipment {

        return $this->update(

            $shipment,

            [

                'tracking_number'
                    => $trackingNumber,

            ]

        );

    }

    /*
    |--------------------------------------------------------------------------
    | Mark Picked Up
    |--------------------------------------------------------------------------
    */

    public function markPickedUp(
        Shipment $shipment
    ): Shipment {

        return $this->update(

            $shipment,

            [

                'status'
                    => 'picked_up',

                'picked_up_at'
                    => now(),

            ]

        );

    }

    /*
    |--------------------------------------------------------------------------
    | Mark In Transit
    |--------------------------------------------------------------------------
    */

    public function markInTransit(
        Shipment $shipment
    ): Shipment {

        return $this->update(

            $shipment,

            [

                'status'
                    => 'in_transit',

            ]

        );

    }

    /*
    |--------------------------------------------------------------------------
    | Mark Delivered
    |--------------------------------------------------------------------------
    */

    public function markDelivered(
        Shipment $shipment
    ): Shipment {

        return $this->update(

            $shipment,

            [

                'status'
                    => 'delivered',

                'delivered_at'
                    => now(),

            ]

        );

    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Shipment
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Shipment $shipment
    ): Shipment {

        return $this->update(

            $shipment,

            [

                'status'
                    => 'cancelled',

            ]

        );

    }

    /*
    |--------------------------------------------------------------------------
    | Find By Tracking
    |--------------------------------------------------------------------------
    */

    public function findByTracking(
        string $trackingNumber
    ): ?Shipment {

        return Shipment::where(

            'tracking_number',

            $trackingNumber

        )->first();

    }

    /*
    |--------------------------------------------------------------------------
    | Find By Booking Code
    |--------------------------------------------------------------------------
    */

    public function findByBookingCode(
        string $bookingCode
    ): ?Shipment {

        return Shipment::where(

            'booking_code',

            $bookingCode

        )->first();

    }
}