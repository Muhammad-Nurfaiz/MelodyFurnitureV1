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
        ?string $createdBy = null,
    ): OrderStatusHistory {

        return OrderStatusHistory::create([

            'order_id' => $order->id,

            'status' => $status,

            'description' => $description,

            'created_by' => $createdBy,

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
        ?string $createdBy = null,
    ): OrderStatusHistory {

        return $this->record(
            $order,
            'pending',
            $description,
            $createdBy
        );

    }

    public function paid(
        Order $order,
        ?string $description = null,
        ?string $createdBy = null,
    ): OrderStatusHistory {

        return $this->record(
            $order,
            'paid',
            $description,
            $createdBy
        );

    }

    public function cancelled(
        Order $order,
        ?string $description = null,
        ?string $createdBy = null,
    ): OrderStatusHistory {

        return $this->record(
            $order,
            'cancelled',
            $description,
            $createdBy
        );

    }

    public function processing(
        Order $order,
        ?string $description = null,
        ?string $createdBy = null,
    ): OrderStatusHistory {

        return $this->record(
            $order,
            'processing',
            $description,
            $createdBy
        );

    }

    public function pickedUp(
        Order $order,
        ?string $description = null,
        ?string $createdBy = null,
    ): OrderStatusHistory {

        return $this->record(
            $order,
            'picked_up',
            $description,
            $createdBy
        );

    }

    public function completed(
        Order $order,
        ?string $description = null,
        ?string $createdBy = null,
    ): OrderStatusHistory {

        return $this->record(
            $order,
            'completed',
            $description,
            $createdBy
        );

    }
}