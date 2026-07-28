<?php

namespace App\Services\Payment;

use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use App\Services\Payment\MidtransPayloadBuilder;

class MidtransService
{
    public function __construct(
        protected MidtransPayloadBuilder $builder
    ){
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /*
    |--------------------------------------------------------------------------
    | Create Snap Transaction
    |--------------------------------------------------------------------------
    */

    public function createTransaction(Order $order): array {
        $payload = $this->builder->build($order);
        $snap = Snap::createTransaction($payload);
        return [
            'transaction_id' => null,
            'order_id' => $order->midtrans_order_id,
            'snap_token' => $snap->token,
            'redirect_url' => $snap->redirect_url,
            'expiry_time' => $order->payment_expired_at,
            'payload' => $payload,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Midtrans API
    |--------------------------------------------------------------------------
    */

    public function status(string $orderNumber): object {
        return Transaction::status($orderNumber);
    }

    public function cancel(string $orderNumber): object {
        return Transaction::cancel($orderNumber);
    }

    public function expire(string $orderNumber): object {
        return Transaction::expire($orderNumber);
    }

    public function refund(string $orderNumber,array $params = []): object {
        return Transaction::refund($orderNumber,$params);
    }
}