<?php

namespace App\Services\Customer;

use App\Models\Order;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Order\OrderQueryService;
use App\Services\Order\OrderWorkflowService;

class CustomerTrackingService
{
    public function __construct(
        protected OrderQueryService $queryService,
        protected OrderWorkflowService $workflowService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Tracking Detail
    |--------------------------------------------------------------------------
    */

    public function detail(
        string $trackingToken
    ): array {
        $order = $this->findOrder(
            $trackingToken
        );

        return [
            /*
            |--------------------------------------------------------------------------
            | Order
            |--------------------------------------------------------------------------
            */
            'order' => $order,
            /*
            |--------------------------------------------------------------------------
            | Customer Actions
            |--------------------------------------------------------------------------
            */
            'actions' =>
                $this->workflowService
                    ->customerActions($order),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Tracking Exists
    |--------------------------------------------------------------------------
    */

    public function exists(
        string $trackingToken
    ): bool {
        return $this->queryService
            ->byTrackingToken(
                $trackingToken
            )
            ->exists();
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