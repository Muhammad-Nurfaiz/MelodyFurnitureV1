<?php

namespace App\Http\Controllers\Admin\Shipment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderFulfillmentService;
use App\Services\Shipping\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\Shipping\ShipmentService;

class ShipmentController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService,
        protected DeliveryService $deliveryService,
    ) {}

    public function store(
        Order $order
    ): JsonResponse {

        $shipment = $this->shipmentService->createManual(
            order: $order,
            admin: auth()->user(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Shipment manual berhasil dibuat.',
            'data' => [
                'shipment' => $shipment,
            ],
        ], 201);
    }

    public function trackingNumber(
        Request $request,
        Order $order
    ): JsonResponse {

        $validated = $request->validate([
            'tracking_number' => [
                'required',
                'string',
                'min:5',
                'max:100',
            ],
        ]);

        abort_unless(
            $order->shipment !== null,
            404,
            'Shipment tidak ditemukan.'
        );

        $shipment = $this->shipmentService->setTrackingNumber(
            shipment: $order->shipment,
            trackingNumber: $validated['tracking_number'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Nomor resi berhasil disimpan.',
            'data' => [
                'shipment' => $shipment,
            ],
        ]);
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