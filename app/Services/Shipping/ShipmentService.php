<?php

namespace App\Services\Shipping;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Order\OrderWorkflowService;

class ShipmentService
{
    public function __construct(
        protected OrderWorkflowService $workflowService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Create Shipment
    |--------------------------------------------------------------------------
    */

    public function create(Order $order,array $shipment): Shipment {
        $shipment = Shipment::create([
            'order_id'        => $order->id,
            'courier'         => $order->courier,
            'service'         => $order->shipping_service,
            'booking_code'    => $shipment['booking_code'] ?? null,
            'tracking_number' => $shipment['tracking_number'] ?? null,
            'label_url'       => $shipment['label_url'] ?? null,
            'status'          => $shipment['status'] ?? 'waiting_pickup',
            'metadata'        => $shipment['metadata'] ?? null,
        ]);
        return $this->refreshShipment($shipment);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Shipment
    |--------------------------------------------------------------------------
    */

    private function updateShipment(Shipment $shipment,array $attributes): Shipment {
        $shipment->update($attributes);
        return $this->refreshShipment($shipment);
    }

    /*
    |--------------------------------------------------------------------------
    | Set Tracking Number
    |--------------------------------------------------------------------------
    */

    public function setTrackingNumber(Shipment $shipment,string $trackingNumber): Shipment {
        return $this->updateShipment($shipment,['tracking_number' => $trackingNumber,]);
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Picked Up
    |--------------------------------------------------------------------------
    */

    public function markPickedUp(Shipment $shipment,Admin $admin): Shipment {
        $this->validatePickup($shipment);
        return DB::transaction(function () use ($shipment,$admin) {
            $shipment = $this->updateShipment($shipment,['status' => 'picked_up','picked_up_at' => now(),]);
            $this->workflowService->changeStatus($shipment->order,'picked_up','Barang telah diambil kurir.',$admin->name);
            return $this->refreshShipment($shipment);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Mark In Transit
    |--------------------------------------------------------------------------
    */

    public function markInTransit(Shipment $shipment,Admin $admin): Shipment {
        $this->validateTransit($shipment);
        return DB::transaction(function () use ($shipment,$admin) {
            $shipment = $this->updateShipment($shipment,['status' => 'in_transit',]);
            $this->workflowService->changeStatus($shipment->order,'shipped','Barang sedang dikirim.',$admin->name);
            return $this->refreshShipment($shipment);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Delivered
    |--------------------------------------------------------------------------
    */

    public function markDelivered(Shipment $shipment,Admin $admin): Shipment {
        $this->validateDelivered($shipment);
        return DB::transaction(function () use ($shipment,$admin) {
            $shipment = $this->updateShipment($shipment,['status' => 'delivered','delivered_at' => now(),]);
            $this->workflowService->changeStatus($shipment->order,'completed','Pesanan telah diterima customer.',$admin->name);
            return $this->refreshShipment($shipment);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Shipment
    |--------------------------------------------------------------------------
    */

    public function cancel(Shipment $shipment,Admin $admin): Shipment {
        return DB::transaction(function () use ($shipment,$admin) {
            $shipment = $this->updateShipment($shipment,['status' => 'cancelled',]);
            $this->workflowService->changeStatus($shipment->order,'processing','Pengiriman dibatalkan.',$admin->name);
            return $this->refreshShipment($shipment);
        });

    }

    /*
    |--------------------------------------------------------------------------
    | Find By Tracking
    |--------------------------------------------------------------------------
    */

    public function findByTracking(string $trackingNumber): ?Shipment {
        return Shipment::with('order','order.customer',)->where('tracking_number',$trackingNumber)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Find By Booking Code
    |--------------------------------------------------------------------------
    */

    public function findByBookingCode(string $bookingCode): ?Shipment {
        return Shipment::where('booking_code',$bookingCode)->first();
    }

    private function validatePickup(Shipment $shipment): void {
        if (!$shipment->isWaitingPickup()) {
            throw new RuntimeException('Shipment tidak dapat dipick up.');
        }
    }

    private function validateTransit(Shipment $shipment): void {
        if (!$shipment->isPickedUp()) {
            throw new RuntimeException('Shipment belum diambil kurir.');
        }
    }

    private function validateDelivered(Shipment $shipment): void {
        if (!$shipment->isInTransit()) {
            throw new RuntimeException('Shipment belum dalam perjalanan.');
        }
    }

    private function refreshShipment(Shipment $shipment): Shipment {
        return $shipment->fresh(['order','order.customer','order.payment',]);
    }

}