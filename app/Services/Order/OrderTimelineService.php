<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderStatusHistory;

class OrderTimelineService
{
    /**
     * Simpan history status order.
     */
    public function record(
        Order $order,
        string $status,
        ?string $description = null,
        string $actor = 'system',
        ?string $adminId = null,
    ): OrderStatusHistory {

        return OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status,
            'description' => $description,
            'actor' => $actor,
            'admin_id' => $adminId,
        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    public function pending(
        Order $order,
        ?string $description = null,
        string $actor = 'system',
        ?string $adminId = null,
    ): OrderStatusHistory {

        return $this->record(
            $order,
            'pending',
            $description,
            $actor,
            $adminId
        );

    }

    public function paid(
        Order $order,
        ?string $description = null,
        string $actor = 'system',
        ?string $adminId = null,
    ): OrderStatusHistory {

        return $this->record(
            $order,
            'paid',
            $description,
            $actor,
            $adminId
        );

    }

    public function cancelled(
        Order $order,
        ?string $description = null,
        string $actor = 'system',
        ?string $adminId = null,
    ): OrderStatusHistory {

        return $this->record(
            $order,
            'cancelled',
            $description,
            $actor,
            $adminId
        );

    }

    public function processing(
        Order $order,
        ?string $description = null,
        string $actor = 'system',
        ?string $adminId = null,
    ): OrderStatusHistory {

        return $this->record(
            $order,
            'processing',
            $description,
            $actor,
            $adminId
        );

    }

    public function pickedUp(
        Order $order,
        ?string $description = null,
        string $actor = 'system',
        ?string $adminId = null,
    ): OrderStatusHistory {

        return $this->record(
            $order,
            'picked_up',
            $description,
            $actor,
            $adminId
        );

    }

    public function completed(
        Order $order,
        ?string $description = null,
        string $actor = 'system',
        ?string $adminId = null,
    ): OrderStatusHistory {

        return $this->record(
            $order,
            'completed',
            $description,
            $actor,
            $adminId
        );

    }
}