<?php

namespace App\Services\Payment;

use App\Models\Order;

class MidtransPayloadBuilder
{
    /**
     * Build payload Snap.
     */
    public function build(Order $order): array {
        return [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => (int) $order->total_payment,
            ],
            'customer_details' => [
                'first_name' => $order->customer->name,
                'email' => $order->customer->email,
                'phone' => $order->customer->phone,
            ],
            'item_details' => $this->buildItems($order),
            'expiry' => [
                'unit' => 'minute',
                'duration' => config('payment.expired_minutes'),
            ],
        ];
    }

    /**
     * Item Details
     */
    private function buildItems(Order $order): array {
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

        if ($order->voucher_discount > 0) {
            $items[] = [
                'id' => 'voucher',
                'name' => 'Diskon Voucher',
                'price' => -(int) $order->voucher_discount,
                'quantity' => 1,
            ];
        }
        return $items;
    }
}