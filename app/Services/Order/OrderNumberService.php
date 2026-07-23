<?php

namespace App\Services\Order;

use App\Models\Order;

class OrderNumberService
{
    /**
     * Generate nomor order
     *
     * Format:
     * MLD-YYYYMMDD-000001
     */
    public function generate(): string
    {
        $today = now()->format('Ymd');

        $lastOrder = Order::query()
            ->whereDate('created_at', today())
            ->lockForUpdate()
            ->latest()
            ->first();

        $running = 1;

        if ($lastOrder) {

            $parts = explode('-', $lastOrder->order_number);

            $running = (int) end($parts) + 1;

        }

        return sprintf(
            'MLD-%s-%06d',
            $today,
            $running
        );
    }
}