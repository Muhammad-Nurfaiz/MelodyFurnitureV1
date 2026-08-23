<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Services\Shipping\CourierService;
use App\Services\Shipping\ShipmentService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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

    /**
     * Memulai proses fulfillment order.
     *
     * Flow:
     *
     * Order:
     * paid
     *   ↓
     * processing
     *
     * Courier:
     * create shipment
     *
     * Local:
     * create shipment
     *
     * Proteksi:
     * - order dikunci menggunakan lockForUpdate()
     * - shipment dicek ulang setelah lock
     * - status dicek ulang setelah lock
     * - satu order hanya boleh memiliki satu shipment
     */
    public function start(
        Order $order,
        ?string $createdBy = 'admin'
    ): Order {

        return DB::transaction(function () use (
            $order,
            $createdBy
        ) {

            /*
            |--------------------------------------------------------------------------
            | Lock Order
            |--------------------------------------------------------------------------
            |
            | Mencegah dua request fulfillment berjalan bersamaan
            | untuk order yang sama.
            |
            */

            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Shipment
            |--------------------------------------------------------------------------
            |
            | Setelah mendapatkan lock, lakukan pengecekan ulang.
            |
            | Ini penting karena request kedua mungkin sudah masuk
            | ketika request pertama masih berjalan.
            |
            */

            if ($order->hasShipment()) {
                throw new RuntimeException(
                    'Order sudah memiliki shipment.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Order Transition
            |--------------------------------------------------------------------------
            */

            $this->workflowService->validate(
                $order,
                'processing'
            );

            /*
            |--------------------------------------------------------------------------
            | Create Shipment At Courier
            |--------------------------------------------------------------------------
            |
            | Order masih berada pada status paid.
            |
            | Jika courier gagal, transaction akan rollback
            | dan order tetap paid.
            |
            */

            $shipmentResult = $this->courierService
                ->createShipment($order);

            /*
            |--------------------------------------------------------------------------
            | Handle Courier Failure
            |--------------------------------------------------------------------------
            */

            if (! $shipmentResult->success) {

                throw new RuntimeException(
                    $shipmentResult->message
                    ?? 'Gagal membuat shipment pada ekspedisi.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Change Order Status
            |--------------------------------------------------------------------------
            */

            $this->workflowService->changeStatus(
                $order,
                'processing',
                'Pesanan mulai diproses.',
                $createdBy
            );

            /*
            |--------------------------------------------------------------------------
            | Create Local Shipment
            |--------------------------------------------------------------------------
            */

            $this->shipmentService->create(
                $order->fresh(),
                $shipmentResult
            );

            /*
            |--------------------------------------------------------------------------
            | Return Fresh Order
            |--------------------------------------------------------------------------
            */

            return $order->fresh([
                'shipment',
                'payment',
            ]);
        });
    }
}