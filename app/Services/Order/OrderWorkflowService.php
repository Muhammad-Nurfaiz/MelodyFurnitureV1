<?php

namespace App\Services\Order;

use App\Models\Order;
use RuntimeException;

class OrderWorkflowService
{
    public function __construct(
        protected OrderTimelineService $timelineService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Initialize Workflow
    |--------------------------------------------------------------------------
    */

    public function initialize(
        Order $order,
        ?string $description = null,
    ): Order {

        $this->timelineService->record(
            $order,
            $order->status,
            $description,
            'system'
        );

        return $order->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow Transition
    |--------------------------------------------------------------------------
    */

    protected array $transitions = [

        'pending' => [
            'paid',
            'cancelled',
        ],

        'paid' => [
            'processing',
            'req_cancel',
            'cancelled',
        ],

        'processing' => [
            'picked_up',
            'req_cancel',
            'cancelled',
        ],

        'req_cancel' => [
            'processing',
            'cancelled',
        ],

        'picked_up' => [
            'shipped',
            'req_cancel',
            'cancelled',
        ],

        'shipped' => [
            'completed',
        ],

        'completed' => [],

        'cancelled' => [],

    ];

    /*
    |--------------------------------------------------------------------------
    | Status Date Mapping
    |--------------------------------------------------------------------------
    */

    protected array $statusDates = [

        'paid' => 'paid_at',

        'picked_up' => 'picked_up_at',

        'shipped' => 'shipped_at',

        'completed' => 'completed_at',

        'cancelled' => 'cancelled_at',

    ];

    /*
    |--------------------------------------------------------------------------
    | Transition Checker
    |--------------------------------------------------------------------------
    */

    public function canTransition(
        Order $order,
        string $target
    ): bool {

        return in_array(
            $target,
            $this->transitions[$order->status] ?? [],
            true
        );
    }

    public function validate(
        Order $order,
        string $target
    ): void {

        if (! $this->canTransition($order, $target)) {
            throw new RuntimeException(
                "Status {$order->status} tidak dapat berubah menjadi {$target}."
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Change Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(
        Order $order,
        string $status,
        ?string $description = null,
        ?string $createdBy = null,
    ): Order {

        $this->validate(
            $order,
            $status
        );

        $data = [
            'status' => $status,
        ];

        if (isset($this->statusDates[$status])) {
            $data[$this->statusDates[$status]] = now();
        }

        $order->update($data);

        $this->timelineService->record(
            $order,
            $status,
            $description,
            $createdBy ? 'admin' : 'system',
            $createdBy
        );

        return $order->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Available Transition
    |--------------------------------------------------------------------------
    */

    public function availableTransitions(
        Order $order
    ): array {

        return $this->transitions[$order->status] ?? [];
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Available Actions
    |--------------------------------------------------------------------------
    */

    public function customerActions(
        Order $order
    ): array {

        $canPay =
            $order->status !== 'cancelled'
            &&
            $order->payment_status === 'pending'
            &&
            filled($order->payment_expired_at)
            &&
            now()->lessThanOrEqualTo($order->payment_expired_at);

        return [

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'can_pay' => $canPay,

            /*
            |--------------------------------------------------------------------------
            | Cancellation
            |--------------------------------------------------------------------------
            */

            'can_request_cancel' =>
                in_array(
                    $order->status,
                    [
                        'pending',
                        'paid',
                        'processing',
                        'picked_up',
                        'shipped',
                    ],
                    true
                ) && is_null($order->cancellationRequest),

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            'can_track_shipping' =>
                in_array(
                    $order->status,
                    [
                        'picked_up',
                        'shipped',
                        'completed',
                    ],
                    true
                ),

            /*
            |--------------------------------------------------------------------------
            | Invoice
            |--------------------------------------------------------------------------
            */

            'can_download_invoice' =>
                in_array(
                    $order->status,
                    [
                        'paid',
                        'processing',
                        'picked_up',
                        'shipped',
                        'completed',
                    ],
                    true
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isCompleted(Order $order): bool
    {
        return $order->status === 'completed';
    }

    public function isCancelled(Order $order): bool
    {
        return $order->status === 'cancelled';
    }

    public function isPendingPayment(Order $order): bool
    {
        return $order->payment_status === 'pending';
    }
}