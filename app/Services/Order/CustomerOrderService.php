<?php

namespace App\Services\Order;

use App\Models\Order;
use RuntimeException;
use App\Http\Requests\Customer\CancellationRequest;

class CustomerOrderService
{
    public function __construct(
        protected OrderQueryService $queryService,
        protected OrderCancellationService $cancellationService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Tracking Order
    |--------------------------------------------------------------------------
    */

    public function tracking(
        string $trackingToken
    ): ?Order {
        return $this->queryService
            ->findByTrackingToken(
                $trackingToken
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Tracking Detail
    |--------------------------------------------------------------------------
    */

    public function trackingDetail(
        string $trackingToken
    ): Order {
        $order = $this->tracking(
            $trackingToken
        );
        if (! $order) {
            throw new RuntimeException(
                'Order tidak ditemukan.'
            );
        }
        return $order;
    }

    /*
    |--------------------------------------------------------------------------
    | Tracking Summary
    |--------------------------------------------------------------------------
    */

    public function trackingSummary(
        string $trackingToken
    ): array {
        $order = $this->trackingDetail(
            $trackingToken
        );
        return [
            'order_number'       => $order->order_number,
            'status'             => $order->status,
            'payment_status'     => $order->payment_status,
            'tracking_number'    => $order->tracking_number,
            'courier'            => $order->courier,
            'shipping_service'   => $order->shipping_service,
            'payment_expired_at' => $order->payment_expired_at,
            'created_at'         => $order->created_at,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Cancellation Request
    |--------------------------------------------------------------------------
    */

    public function requestCancellation(
        CancellationRequest $request,
        string $trackingToken
    ): JsonResponse
    {
        $order = $this->customerOrderService
            ->requestCancellation(
                $trackingToken,
                $request->validated()
            );

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan pembatalan berhasil dikirim.',
            'tracking_token' => $order->tracking_token,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Can Request Cancellation
    |--------------------------------------------------------------------------
    */

    public function canRequestCancellation(
        string $trackingToken
    ): bool {
        $order = $this->tracking(
            $trackingToken
        );
        if (! $order) {
            return false;
        }
        return $this->cancellationService
            ->canRequest(
                $order
            );
    }
}