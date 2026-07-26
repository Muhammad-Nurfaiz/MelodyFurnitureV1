<?php

namespace App\Services\Customer;

use App\Models\Order;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Order\OrderQueryService;
use App\Services\Order\OrderCancellationService;

class CustomerCancellationService
{
    public function __construct(
        protected OrderQueryService $queryService,
        protected OrderCancellationService $cancellationService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Request Cancellation
    |--------------------------------------------------------------------------
    */

    public function request(
        string $trackingToken,
        string $reason,
    ): Order {

        $order = $this->findOrder(
            $trackingToken
        );

        $this->cancellationService
            ->requestByCustomer(

                order: $order,

                reason: $reason,

                note: $note,

            );

        return $this->queryService
            ->findByTrackingToken(
                $trackingToken
            );

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