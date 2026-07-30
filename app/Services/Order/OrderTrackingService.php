<?php

namespace App\Services\Order;

use App\Models\Order;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderTrackingService
{
    /**
     * Get order by tracking token.
     */
    public function findByTrackingToken(string $trackingToken): Order {

        $order = Order::query()
            ->where('tracking_token', $trackingToken)
            ->with([
                'customer',
                'payment',
                'voucher',
                'items.product',
                'statusHistories',
                'shipment',
            ])
            ->first();

        if (! $order) {
            throw new ModelNotFoundException('Order tidak ditemukan.');
        }
        return $order;
    }

    
}