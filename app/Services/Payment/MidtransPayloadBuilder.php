<?php

namespace App\Services\Payment;

use App\Models\Order;

class MidtransPayloadBuilder
{
    private const PAYMENT_RESULT = '/payment/result';

    /**
     * Build Snap Payload
     */
    public function build(Order $order): array
    {
        $paymentResultUrl =
            config('app.frontend_url')
            . self::PAYMENT_RESULT
            . '?order_id='
            . urlencode($order->midtrans_order_id);

        return [
            'transaction_details' => [
                'order_id' => $order->midtrans_order_id,
                'gross_amount' => (int) $order->total_payment,
            ],

            'customer_details' => [
                'first_name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],

            'enabled_payments' => config(
                'payment.midtrans.enabled_payments'
            ),

            'item_details' => $this->buildItems($order),

            'expiry' => [
                'unit' => 'minute',
                'duration' => (int) config(
                    'payment.expired_minutes'
                ),
            ],

            'callbacks' => [
                'finish' => $paymentResultUrl,
                'pending' => $paymentResultUrl,
                'error' => $paymentResultUrl,
            ],
        ];
    }

    /**
     * Item Details
     */
    private function buildItems(Order $order): array
    {
        $items = [];

        foreach ($order->items as $item) {
            $items[] = [
                'id' => $item->product_id,
                'name' => $item->product_name,
                'price' => (int) $item->unit_price,
                'quantity' => $item->quantity,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Shipping
        |--------------------------------------------------------------------------
        */

        if ($order->shipping_fee > 0) {
            $items[] = [
                'id' => 'shipping',
                'name' => 'Biaya Pengiriman',
                'price' => (int) $order->shipping_fee,
                'quantity' => 1,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Voucher
        |--------------------------------------------------------------------------
        */

        if ($order->voucher_discount_amount > 0) {
            $items[] = [
                'id' => 'voucher',
                'name' => 'Diskon Voucher',
                'price' => -(int) $order->voucher_discount_amount,
                'quantity' => 1,
            ];
        }

        return $items;
    }
}