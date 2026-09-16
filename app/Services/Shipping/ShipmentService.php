<?php

namespace App\Services\Shipping;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Order\OrderWorkflowService;
use App\Services\Shipping\Courier\CourierShipmentResult;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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

    public function create(
        Order $order,
        CourierShipmentResult $result
    ): Shipment {

        return DB::transaction(function () use (
            $order,
            $result
        ) {

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Shipment
            |--------------------------------------------------------------------------
            */

            if ($order->hasShipment()) {
                throw new RuntimeException(
                    'Order sudah memiliki shipment.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Order
            |--------------------------------------------------------------------------
            */

            if (! $order->isProcessing()) {
                throw new RuntimeException(
                    'Shipment hanya dapat dibuat ketika order sedang diproses.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Courier Result
            |--------------------------------------------------------------------------
            */

            if (! $result->success) {
                throw new RuntimeException(
                    $result->message
                    ?? 'Gagal membuat shipment pada ekspedisi.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create Local Shipment
            |--------------------------------------------------------------------------
            */

            $shipment = Shipment::create([

                'order_id' => $order->id,

                'courier' => $order->courier,

                'service' => $order->shipping_method,

                'booking_code' => $result->bookingCode,

                'tracking_number' => $result->trackingNumber,

                'label_url' => $result->labelUrl,

                'status' =>
                    $result->status
                    ?? 'waiting_pickup',

                'metadata' => $result->metadata,

            ]);

            /*
            |--------------------------------------------------------------------------
            | Sync Tracking Number To Order
            |--------------------------------------------------------------------------
            */

            if (filled($result->trackingNumber)) {

                $order->update([
                    'tracking_number'
                        => $result->trackingNumber,
                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | Return Fresh Shipment
            |--------------------------------------------------------------------------
            */

            return $this->refreshShipment(
                $shipment
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Shipment
    |--------------------------------------------------------------------------
    */

    private function updateShipment(
        Shipment $shipment,
        array $attributes
    ): Shipment {

        $shipment->update(
            $attributes
        );

        return $this->refreshShipment(
            $shipment
        );
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

        return DB::transaction(
            function () use (
                $shipment,
                $trackingNumber
            ) {

                $shipment = $this->updateShipment(
                    $shipment,
                    [
                        'tracking_number'
                            => $trackingNumber,
                    ]
                );

                $shipment->order()->update([
                    'tracking_number'
                        => $trackingNumber,
                ]);

                return $this->refreshShipment(
                    $shipment
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Picked Up
    |--------------------------------------------------------------------------
    */

    public function markPickedUp(
        Shipment $shipment,
        Admin $admin
    ): Shipment {

        $this->validatePickup(
            $shipment
        );

        return DB::transaction(
            function () use (
                $shipment,
                $admin
            ) {

                $shipment = $this->updateShipment(
                    $shipment,
                    [
                        'status'
                            => 'picked_up',

                        'picked_up_at'
                            => now(),
                    ]
                );

                $this->workflowService->changeStatus(
                    $shipment->order,
                    'picked_up',
                    'Barang telah diambil kurir.',
                    (string) $admin->id
                );

                return $this->refreshShipment(
                    $shipment
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark In Transit
    |--------------------------------------------------------------------------
    */

    public function markInTransit(
        Shipment $shipment,
        Admin $admin
    ): Shipment {

        $this->validateTransit(
            $shipment
        );

        return DB::transaction(
            function () use (
                $shipment,
                $admin
            ) {

                $shipment = $this->updateShipment(
                    $shipment,
                    [
                        'status'
                            => 'in_transit',
                    ]
                );

                $this->workflowService->changeStatus(
                    $shipment->order,
                    'shipped',
                    'Barang sedang dikirim.',
                    (string) $admin->id
                );

                return $this->refreshShipment(
                    $shipment
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Delivered
    |--------------------------------------------------------------------------
    */

    public function markDelivered(
        Shipment $shipment,
        Admin $admin
    ): Shipment {

        $this->validateDelivered(
            $shipment
        );

        return DB::transaction(
            function () use (
                $shipment,
                $admin
            ) {

                $shipment = $this->updateShipment(
                    $shipment,
                    [
                        'status'
                            => 'delivered',

                        'delivered_at'
                            => now(),
                    ]
                );

                $this->workflowService->changeStatus(
                    $shipment->order,
                    'completed',
                    'Pesanan telah diterima customer.',
                    (string) $admin->id
                );

                return $this->refreshShipment(
                    $shipment
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Shipment
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Shipment $shipment,
        Admin $admin
    ): Shipment {

        $this->validateCancellation(
            $shipment
        );

        return DB::transaction(
            function () use (
                $shipment,
                $admin
            ) {

                $shipment = $this->updateShipment(
                    $shipment,
                    [
                        'status'
                            => 'cancelled',
                    ]
                );

                $this->workflowService->changeStatus(
                    $shipment->order,
                    'processing',
                    'Pengiriman dibatalkan.',
                    (string) $admin->id
                );

                return $this->refreshShipment(
                    $shipment
                );
            }
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

        return Shipment::with([
            'order',
            'order.payment',
        ])
            ->where(
                'tracking_number',
                $trackingNumber
            )
            ->first();
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

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validatePickup(
        Shipment $shipment
    ): void {

        if (! $shipment->isWaitingPickup()) {

            throw new RuntimeException(
                'Shipment tidak dapat dipick up.'
            );
        }
    }

    private function validateTransit(
        Shipment $shipment
    ): void {

        if (! $shipment->isPickedUp()) {

            throw new RuntimeException(
                'Shipment belum diambil kurir.'
            );
        }
    }

    private function validateDelivered(
        Shipment $shipment
    ): void {

        if (! $shipment->isInTransit()) {

            throw new RuntimeException(
                'Shipment belum dalam perjalanan.'
            );
        }
    }

    private function validateCancellation(
        Shipment $shipment
    ): void {

        if ($shipment->isDelivered()) {

            throw new RuntimeException(
                'Shipment yang sudah delivered tidak dapat dibatalkan.'
            );
        }

        if ($shipment->isCancelled()) {

            throw new RuntimeException(
                'Shipment sudah dibatalkan.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Refresh Shipment
    |--------------------------------------------------------------------------
    */

    private function refreshShipment(
        Shipment $shipment
    ): Shipment {

        return $shipment->fresh([
            'order',
            'order.payment',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Manual Shipment
    |--------------------------------------------------------------------------
    */

    /**
     * Create shipment secara manual oleh admin.
     *
     * Flow:
     *
     * Order:
     * processing
     *     ↓
     *
     * Shipment:
     * waiting_pickup
     *
     * Tracking number belum wajib pada tahap ini.
     * Admin dapat menginput resi pada tahap berikutnya.
     */
    public function createManual(
        Order $order,
        Admin $admin,
    ): Shipment {

        return DB::transaction(function () use (
            $order,
            $admin
        ) {

            /*
            |--------------------------------------------------------------------------
            | Lock Order
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new RuntimeException(
                    'Order tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Order Status
            |--------------------------------------------------------------------------
            */

            if ($order->status !== 'processing') {
                throw new RuntimeException(
                    'Shipment hanya dapat dibuat untuk order yang berstatus processing.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Shipment
            |--------------------------------------------------------------------------
            */

            if (
                Shipment::query()
                    ->where('order_id', $order->id)
                    ->exists()
            ) {
                throw new RuntimeException(
                    'Order sudah memiliki shipment.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create Shipment
            |--------------------------------------------------------------------------
            */

            $shipment = Shipment::create([
                'order_id'        => $order->id,
                'courier'         => $order->courier,
                'service'         => $order->shipping_method,
                'booking_code'    => null,
                'tracking_number' => null,
                'label_url'       => null,
                'status'          => 'waiting_pickup',
                'metadata'        => [
                    'source' => 'manual',
                    'created_by' => $admin->id,
                    'created_at' => now()->toISOString(),
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Return Fresh Shipment
            |--------------------------------------------------------------------------
            */

            return $this->refreshShipment($shipment);
        });
    }

    public function syncTracking(
        Shipment $shipment,
        CourierShipmentResult $result
    ): Shipment {
        if (! $result->success) {
            throw new RuntimeException(
                $result->message
                ?? 'Gagal melakukan sinkronisasi tracking shipment.'
            );
        }

        $syncedAt = now();

        $metadata = $shipment->metadata ?? [];

        $metadata['tracking'] = $result->metadata;
        $metadata['tracking_synced_at'] = $syncedAt->toISOString();

        $shipment->update([
            'tracking_number' => $result->trackingNumber
                ?? $shipment->tracking_number,
            'metadata' => $metadata,
            'last_tracking_sync_at' => $syncedAt,
        ]);

        return $shipment->fresh();
    }

    public function syncCourierStatus(
        Shipment $shipment,
        ?string $mappedStatus
    ): Shipment {

        if (blank($mappedStatus)) {
            return $shipment->fresh();
        }

        $statusOrder = [
            'waiting_pickup' => 1,
            'picked_up'      => 2,
            'in_transit'     => 3,
            'delivered'      => 4,
        ];

        $currentStatus = $shipment->status;

        $currentRank = $statusOrder[$currentStatus] ?? 0;
        $newRank = $statusOrder[$mappedStatus] ?? 0;

        /*
        |--------------------------------------------------------------------------
        | Unknown Status
        |--------------------------------------------------------------------------
        */

        if ($newRank === 0) {
            return $shipment->fresh();
        }

        /*
        |--------------------------------------------------------------------------
        | Jangan pernah menurunkan status shipment
        |--------------------------------------------------------------------------
        */

        if ($newRank <= $currentRank) {
            return $shipment->fresh();
        }

        return DB::transaction(function () use (
            $shipment,
            $mappedStatus
        ) {

            /*
            |--------------------------------------------------------------------------
            | Lock Shipment
            |--------------------------------------------------------------------------
            */
            $shipment = Shipment::query()
                ->whereKey($shipment->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Lock Order
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->whereKey($shipment->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Re-check Status
            |--------------------------------------------------------------------------
            */

            $statusOrder = [
                'waiting_pickup' => 1,
                'picked_up'      => 2,
                'in_transit'     => 3,
                'delivered'      => 4,
            ];

            $currentRank =
                $statusOrder[$shipment->status] ?? 0;

            $newRank =
                $statusOrder[$mappedStatus] ?? 0;

            /*
            |--------------------------------------------------------------------------
            | Status Tidak Valid / Sudah Lebih Maju
            |--------------------------------------------------------------------------
            */

            if (
                $newRank === 0 ||
                $newRank <= $currentRank
            ) {
                return $this->refreshShipment(
                    $shipment
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Picked Up
            |--------------------------------------------------------------------------
            */

            if ($mappedStatus === 'picked_up') {

                $shipment->update([
                    'status' => 'picked_up',
                    'picked_up_at' =>
                        $shipment->picked_up_at
                        ?? now(),
                ]);

                if ($order->status === 'processing') {

                    $this->workflowService->changeStatus(
                        $order,
                        'picked_up',
                        'Barang telah diambil kurir.',
                        null
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | In Transit
            |--------------------------------------------------------------------------
            */

            elseif ($mappedStatus === 'in_transit') {

                /*
                * Jika order masih processing,
                * berarti pickup sebelumnya belum tercatat
                * secara lokal.
                *
                * Karena courier sudah memberikan status
                * in_transit, kita sinkronkan milestone
                * pickup terlebih dahulu.
                */

                if ($order->status === 'processing') {

                    $this->workflowService->changeStatus(
                        $order,
                        'picked_up',
                        'Barang telah diambil kurir.',
                        null
                    );
                }

                /*
                * Setelah picked_up, lanjut shipped.
                */

                if ($order->status === 'picked_up') {

                    $this->workflowService->changeStatus(
                        $order,
                        'shipped',
                        'Barang sedang dikirim.',
                        null
                    );
                }

                $shipment->update([
                    'status' => 'in_transit',
                    'picked_up_at' =>
                        $shipment->picked_up_at
                        ?? now(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Delivered
            |--------------------------------------------------------------------------
            */

            elseif ($mappedStatus === 'delivered') {

                /*
                * Courier sudah menyatakan barang delivered.
                *
                * Jika milestone sebelumnya belum tercatat
                * secara lokal, kita lengkapi secara berurutan.
                */

                if ($order->status === 'processing') {

                    $this->workflowService->changeStatus(
                        $order,
                        'picked_up',
                        'Barang telah diambil kurir.',
                        null
                    );
                }

                if ($order->status === 'picked_up') {

                    $this->workflowService->changeStatus(
                        $order,
                        'shipped',
                        'Barang sedang dikirim.',
                        null
                    );
                }

                if ($order->status === 'shipped') {

                    $this->workflowService->changeStatus(
                        $order,
                        'completed',
                        'Pesanan telah diterima customer.',
                        null
                    );
                }

                $shipment->update([
                    'status' => 'delivered',

                    'picked_up_at' =>
                        $shipment->picked_up_at
                        ?? now(),

                    'delivered_at' =>
                        $shipment->delivered_at
                        ?? now(),
                ]);
            }

            return $this->refreshShipment(
                $shipment
            );
        });
    }
}