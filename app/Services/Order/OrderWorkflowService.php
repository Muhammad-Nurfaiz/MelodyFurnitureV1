<?php

namespace App\Services\Order;

use App\Models\Order;
use RuntimeException;

class OrderWorkflowService
{
    public function __construct(
        protected OrderTimelineService $timelineService,
    ) {}

    public function initialize(
        Order $order,
        ?string $description = null,
        ?string $createdBy = null,
    ): Order {
        $this->timelineService->record(
            $order,
            $order->status,
            $description,
            $createdBy
        );
        return $order->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow Transition
    |--------------------------------------------------------------------------
    */
    protected array $transitions = [
        'pending' => ['paid','cancelled',],
        'paid' => ['processing','cancelled',],
        'processing' => ['picked_up','cancelled',],
        'picked_up' => ['completed',],
        'completed' => [],
        'cancelled' => [],
    ];

    /*
    |--------------------------------------------------------------------------
    | Status Date Mapping
    |--------------------------------------------------------------------------
    */
    protected array $statusDates = [
        'paid'       => 'paid_at',
        'picked_up'  => 'shipped_at',
        'completed'  => 'completed_at',
        'cancelled'  => 'cancelled_at',
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
        if (
            ! $this->canTransition(
                $order,
                $target
            )
        ) {
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
        if (
            isset($this->statusDates[$status])
        ) {
            $data[
                $this->statusDates[$status]
            ] = now();
        }
        $order->update($data);
        $this->timelineService
            ->record(
                $order,
                $status,
                $description,
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

        return $this->transitions[
            $order->status
        ] ?? [];
    }
}