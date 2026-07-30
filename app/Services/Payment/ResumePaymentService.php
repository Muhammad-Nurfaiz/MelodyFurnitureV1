<?php

namespace App\Services\Payment;

use App\Models\Order;
use RuntimeException;
use App\Services\Order\OrderTrackingService;

class ResumePaymentService
{
    public function __construct(
        protected OrderTrackingService $trackingService,
        protected MidtransService $midtransService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Resume Payment
    |--------------------------------------------------------------------------
    */

    public function resume(string $trackingToken): array
    {
        $order = $this->trackingService
            ->findByTrackingToken($trackingToken);

        $payment = $order->payment;

        if (! $payment) {
            throw new RuntimeException('Data pembayaran tidak ditemukan.');
        }

        /*
        |--------------------------------------------------------------------------
        | Business Validation
        |--------------------------------------------------------------------------
        */

        if ($order->payment_status !== 'pending') {
            return [
                'can_pay' => false,
                'message' => 'Pembayaran sudah selesai.',
            ];
        }

        if ($order->status === 'cancelled') {
            return [
                'can_pay' => false,
                'message' => 'Pesanan sudah dibatalkan.',
            ];
        }

        if (
            filled($order->payment_expired_at)
            && now()->greaterThan($order->payment_expired_at)
        ) {
            return [
                'can_pay' => false,
                'message' => 'Pembayaran sudah kedaluwarsa.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Snap Validation
        |--------------------------------------------------------------------------
        */

        if (
            blank($payment->snap_token)
            || blank($payment->raw_response['redirect_url'] ?? null)
        ) {
            throw new RuntimeException('Link pembayaran tidak ditemukan.');
        }

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        return [
            'can_pay' => true,
            'redirect_url' => $payment->raw_response['redirect_url'],
            'snap_token' => $payment->snap_token,
            'expired_at' => $payment->expired_at,
            'payment_status' => $payment->transaction_status,
        ];
    }
}