<?php

namespace App\Http\Controllers\Admin\Shipment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderFulfillmentService;
use App\Services\Shipping\DeliveryService;
use Illuminate\Http\JsonResponse;

class ShipmentController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService,
        protected OrderFulfillmentService $fulfillmentService
    ) {}

    public function store(
        Order $order
    ): JsonResponse {

        $order = $this->fulfillmentService->start(
            $order,
            auth()->id()
                ? (string) auth()->id()
                : 'admin'
        );

        return response()->json([
            'message' => 'Shipment berhasil dibuat.',
            'data' => $order,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Pickup
    |--------------------------------------------------------------------------
    */

    /**
     * Mark order shipment as picked up by courier.
     */
    public function pickup(
        Order $order
    ): JsonResponse {

        $order = $this->deliveryService->pickup(
            $order,
            (string) auth()->id()
        );

        return response()->json([
            'message' => 'Shipment berhasil dipickup.',
            'data' => $order,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Transit
    |--------------------------------------------------------------------------
    */

    /**
     * Mark order shipment as in transit.
     */
    public function transit(
        Order $order
    ): JsonResponse {

        $order = $this->deliveryService->transit(
            $order,
            (string) auth()->id()
        );

        return response()->json([
            'message' => 'Shipment sedang dikirim.',
            'data' => $order,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delivered
    |--------------------------------------------------------------------------
    */

    /**
     * Mark order shipment as delivered.
     */
    public function delivered(
        Order $order
    ): JsonResponse {

        $order = $this->deliveryService->delivered(
            $order,
            (string) auth()->id()
        );

        return response()->json([
            'message' => 'Shipment berhasil diterima pelanggan.',
            'data' => $order,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel
    |--------------------------------------------------------------------------
    */

    /**
     * Cancel shipment.
     *
     * Cancellation shipment belum diaktifkan karena
     * aturan pembatalan dari perusahaan belum dikonfirmasi.
     */
    public function cancel(
        Order $order
    ): JsonResponse {

        abort_unless(
            $order->shipment !== null,
            404,
            'Shipment tidak ditemukan.'
        );

        return response()->json([
            'message' =>
                'Pembatalan shipment belum tersedia karena aturan pembatalan dari perusahaan belum dikonfirmasi.',
        ], 409);
    }
}