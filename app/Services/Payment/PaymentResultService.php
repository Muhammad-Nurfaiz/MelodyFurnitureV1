<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentResultService
{
    /*
    |--------------------------------------------------------------------------
    | Get Payment Result
    |--------------------------------------------------------------------------
    */

    public function result(string $trackingToken): array
    {
        $order = Order::query()
            ->with(['payment', 'items'])
            ->where('tracking_token', $trackingToken)
            ->first();

        if (! $order) {
            throw new ModelNotFoundException('Order tidak ditemukan.');
        }

        $payment = $order->payment;

        return [
            /*
            |--------------------------------------------------------------------------
            | Order
            |--------------------------------------------------------------------------
            */

            'order_number' => $order->order_number,

            'tracking_token' => $order->tracking_token,

            'total_payment' => $order->total_payment,

            'items' => $order->items->map(
                fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]
            )->values()->all(),

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'status' => $this->mapStatus($order),

            'payment_status' => $order->payment_status,

            'transaction_status' =>
                $payment?->transaction_status,

            'payment_type' =>
                $payment?->payment_type,

            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            'redirect_tracking' =>
                "/tracking/{$order->tracking_token}",

            /*
            |--------------------------------------------------------------------------
            | Message
            |--------------------------------------------------------------------------
            */

            'message' => $this->message($order),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Status Mapping
    |--------------------------------------------------------------------------
    */

    private function mapStatus(Order $order): string
    {
        $transactionStatus = $order->payment?->transaction_status;

        return match ($transactionStatus) {
            'capture',
            'settlement' => 'paid',
            'pending' => 'pending',
            'expire' => 'expired',
            'cancel' => 'cancelled',
            'deny' => 'failed',
            'refund',
            'partial_refund',
            'refunded' => 'refunded',
            default => $order->payment_status === 'paid' ? 'paid' : 'pending',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Message
    |--------------------------------------------------------------------------
    */

    private function message(Order $order): string
    {
        return match ($this->mapStatus($order)) {
            'paid' => 'Pembayaran berhasil.',
            'pending' => 'Menunggu pembayaran.',
            'expired' => 'Pembayaran telah kedaluwarsa.',
            'cancelled' => 'Pesanan telah dibatalkan.',
            'refunded' => 'Pembayaran telah dikembalikan.',
            default => 'Pembayaran gagal.',
        };
    }
}