<?php

namespace App\Services\Customer;

use App\Models\Order;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Order\OrderQueryService;

class CustomerPaymentService
{
    public function __construct(
        protected OrderQueryService $queryService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Payment Information
    |--------------------------------------------------------------------------
    */

    public function information(
        string $trackingToken
    ): array {

        $order = $this->findOrder(
            $trackingToken
        );

        $expired =

            filled($order->payment_expired_at)

            &&

            now()->greaterThan(
                $order->payment_expired_at
            );

        $remainingSeconds =

            $expired || blank($order->payment_expired_at)

                ? 0

                : now()->diffInSeconds(
                    $order->payment_expired_at,
                    false
                );

        return [

            'order' => $order,

            'payment' => $order->payment,

            'expired' => $expired,

            'remaining_seconds' => max(
                0,
                $remainingSeconds
            ),

            'can_pay' =>

                $order->status === 'pending'

                &&

                $order->payment_status === 'pending'

                &&

                ! $expired,

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Find Order
    |--------------------------------------------------------------------------
    */

    protected function findOrder(
        string $trackingToken
    ): Order {

        $order = $this->queryService
            ->findByTrackingToken(
                $trackingToken
            );

        if (! $order) {

            throw new ModelNotFoundException(
                'Order tidak ditemukan.'
            );

        }

        return $order;

    }
}