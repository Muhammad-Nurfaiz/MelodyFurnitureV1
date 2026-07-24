<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\OrderCancelRequest;
use Illuminate\Support\Facades\DB;
use App\Services\Payment\RefundService;
use App\Services\Payment\PaymentService;
use App\Services\Product\ProductInventoryService;
use RuntimeException;

class OrderCancellationService
{
    public function __construct(
        protected OrderWorkflowService $workflowService,
        protected RefundService $refundService,
        protected ProductInventoryService $inventoryService,
        protected PaymentService $paymentService,

    ) {}

    /*
    |--------------------------------------------------------------------------
    | Customer Request Cancellation
    |--------------------------------------------------------------------------
    */

    public function request(
        Order $order,
        Customer $customer,
        string $reason
    ): OrderCancelRequest {
        $this->validateRequest(
            $order,
            $customer
        );
        return DB::transaction(function () use (
            $order,
            $customer,
            $reason
        ) {
            $request =
                OrderCancelRequest::create([
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'reason' => $reason,
                    'previous_status' => $order->status,
                    'status' => 'pending',
                ]);
            $this->workflowService
                ->changeStatus(
                    $order,
                    'req_cancel',
                    'Cancel requested by customer',
                    $customer->admin?->name
                );
            return $request;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Approve
    |--------------------------------------------------------------------------
    */

    public function approve(
        OrderCancelRequest $request,
        Admin $admin,
        ?string $notes = null
    ): Order {
        return DB::transaction(function () use (
            $request,
            $admin,
            $notes
        ) {
            $request->update([
                'status' => 'approved',
                'approved_by' => $admin->id,
                'admin_notes' => $notes,
                'approved_at' => now(),
            ]);
            $order = $request->order;
            $order->loadMissing([

                'payment',

                'items.product',

            ]);

            /*
            |--------------------------------------------------------------------------
            | Restore Stock
            |--------------------------------------------------------------------------
            */

            $products =

                $order->items->map(function ($item) {

                    return [

                        'product' => $item->product,

                        'qty' => $item->quantity,

                    ];

                });

            $this->inventoryService
                ->increaseStock($products);

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            $this->paymentService
                ->markRefunded(
                    $order->payment
                );

            /*
            |--------------------------------------------------------------------------
            | Refund
            |--------------------------------------------------------------------------
            */

            $this->refundService
                ->create($order);

            /*
            |--------------------------------------------------------------------------
            | Workflow
            |--------------------------------------------------------------------------
            */

            $this->workflowService
                ->changeStatus(

                    $order,

                    'cancelled',

                    'Cancellation approved',

                    $admin->name

                );

            return $order->fresh([

                'refund',

                'payment',

                'items.product',

            ]);
            return $order->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Reject
    |--------------------------------------------------------------------------
    */

    public function reject(
        OrderCancelRequest $request,
         $admin,
        ?string $notes = null
    ): Order {
        return DB::transaction(function () use (
            $request,
            $admin,
            $notes
        ) {
            $request->update([
                'status' => 'rejected',
                'approved_by' => $admin->id,
                'admin_notes' => $notes,
                'approved_at' => now(),
            ]);
            $order = $request->order;
            $order->refresh();
            $this->workflowService
                ->changeStatus(
                    $order,
                    $request->previous_status,
                    'Cancellation rejected',
                    $admin->name
                );
            return $order->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Validator
    |--------------------------------------------------------------------------
    */

    protected function validateRequest(
        Order $order,
        Customer $customer
    ): void {
        if ($order->customer_id !== $customer->id) {
            throw new RuntimeException('Order tidak dimiliki customer.');
        }
        if ($order->status === 'cancelled') {
            throw new RuntimeException('Order sudah dibatalkan.');
        }
        if ($order->status === 'completed') {
            throw new RuntimeException('Order sudah selesai.');
        }
        if ($order->cancelRequest) {
            throw new RuntimeException('Order sudah memiliki permintaan pembatalan.');
        }
    }
}