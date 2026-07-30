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

    public function result(string $orderId): array
    {
        $order = Order::query()
            ->with('payment')
            ->where('midtrans_order_id', $orderId)
            ->first();

        if (! $order) {
            throw new ModelNotFoundException(
                'Order tidak ditemukan.'
            );
        }

        return [

            /*
            |--------------------------------------------------------------------------
            | Order
            |--------------------------------------------------------------------------
            */

            'order_number' => $order->order_number,
            'tracking_token' => $order->tracking_token,

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'status' => $this->mapStatus($order),
            'payment_status' => $order->payment_status,

            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            'redirect_tracking' => "/tracking/{$order->tracking_token}",

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
        if ($order->payment_status === 'paid') {
            return 'paid';
        }

        if ($order->payment_status === 'expired') {
            return 'expired';
        }

        if ($order->status === 'cancelled') {
            return 'cancelled';
        }

        if ($order->payment_status === 'pending') {
            return 'pending';
        }

        return 'failed';
    }

    /*
    |--------------------------------------------------------------------------
    | Message
    |--------------------------------------------------------------------------
    */

    private function message(Order $order): string
    {
        return match ($this->mapStatus($order)) {
            'paid' =>'Pembayaran berhasil.',
            'pending' => 'Menunggu pembayaran.',
            'expired' => 'Pembayaran telah kedaluwarsa.',
            'cancelled' => 'Pesanan telah dibatalkan.',
            default => 'Pembayaran gagal.',
        };
    }
}