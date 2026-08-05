<?php

namespace App\Services\Order;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderCancelRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use App\Services\Payment\PaymentService;
use App\Services\Payment\RefundService;
use App\Services\Inventory\ProductInventoryService;

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

    public function requestByCustomer(
        Order $order,
        string $reason
    ): OrderCancelRequest {

        return DB::transaction(function () use ($order, $reason) {

            /*
            |--------------------------------------------------------------------------
            | Lock Order
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new RuntimeException(
                    'Order tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate
            |--------------------------------------------------------------------------
            */

            $this->validateCustomerRequest($order);

            /*
            |--------------------------------------------------------------------------
            | Create Request
            |--------------------------------------------------------------------------
            */

            $request = $this->createCancellationRequest(
                $order,
                $reason
            );

            /*
            |--------------------------------------------------------------------------
            | Move Order
            |--------------------------------------------------------------------------
            */

            $this->workflowService->changeStatus(
                order: $order,
                status: 'req_cancel',
                description: 'Customer mengajukan permintaan pembatalan.',
                adminId: null,
            );

            return $request->fresh([
                'order',
                'customer',
            ]);
        });
    }

    private function createCancellationRequest(
        Order $order,
        string $reason
    ): OrderCancelRequest {

        return OrderCancelRequest::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'reason' => $reason,
            'previous_status' => $order->status,
            'status' => 'pending',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Cancellation
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

            /*
            |--------------------------------------------------------------------------
            | Lock Cancellation Request
            |--------------------------------------------------------------------------
            */

            $request = OrderCancelRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->first();

            if (! $request) {
                throw new RuntimeException(
                    'Permintaan pembatalan tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Lock Order
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->whereKey($request->order_id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new RuntimeException(
                    'Order tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate
            |--------------------------------------------------------------------------
            */

            $this->validateApproval(
                request: $request,
                order: $order,
            );

            /*
            |--------------------------------------------------------------------------
            | Load Relations
            |--------------------------------------------------------------------------
            */

            $order->loadMissing([
                'payment',
                'refund',
                'items.product',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Validate Payment
            |--------------------------------------------------------------------------
            |
            | Customer cancellation pada paid/processing order
            | membutuhkan refund.
            |
            */

            if (! $order->payment) {
                throw new RuntimeException(
                    'Payment untuk order tidak ditemukan.'
                );
            }

            if (! $this->paymentService->isPaid($order->payment)) {
                throw new RuntimeException(
                    'Payment belum berada pada status yang dapat direfund.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Restore Inventory
            |--------------------------------------------------------------------------
            */

            $this->restoreInventory($order);

            /*
            |--------------------------------------------------------------------------
            | Create Refund
            |--------------------------------------------------------------------------
            */

            $this->createRefundRequest($order);

            /*
            |--------------------------------------------------------------------------
            | Approve Request
            |--------------------------------------------------------------------------
            */

            $this->approveCancellationRequest(
                request: $request,
                admin: $admin,
                notes: $notes,
            );

            /*
            |--------------------------------------------------------------------------
            | Cancel Order
            |--------------------------------------------------------------------------
            */

            $this->workflowService->changeStatus(
                order: $order,
                status: 'cancelled',
                description: 'Permintaan pembatalan disetujui oleh admin.',
                adminId: $admin->id,
            );

            return $order->fresh([
                'payment',
                'refund',
                'items.product',
                'cancellationRequest',
            ]);
        });
    }

    private function approveCancellationRequest(
        OrderCancelRequest $request,
        Admin $admin,
        ?string $notes
    ): void {

        $request->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
            'admin_notes' => $notes,
            'approved_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Cancellation
    |--------------------------------------------------------------------------
    */

    public function reject(
        OrderCancelRequest $request,
        Admin $admin,
        ?string $notes = null
    ): Order {

        return DB::transaction(function () use (
            $request,
            $admin,
            $notes
        ) {

            /*
            |--------------------------------------------------------------------------
            | Lock Request
            |--------------------------------------------------------------------------
            */

            $request = OrderCancelRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->first();

            if (! $request) {
                throw new RuntimeException(
                    'Permintaan pembatalan tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Lock Order
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->whereKey($request->order_id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new RuntimeException(
                    'Order tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate
            |--------------------------------------------------------------------------
            */

            $this->validateRejection(
                request: $request,
                order: $order,
            );

            /*
            |--------------------------------------------------------------------------
            | Reject Request
            |--------------------------------------------------------------------------
            */

            $this->rejectCancellationRequest(
                request: $request,
                admin: $admin,
                notes: $notes,
            );

            /*
            |--------------------------------------------------------------------------
            | Restore Previous Order Status
            |--------------------------------------------------------------------------
            */

            $this->workflowService->changeStatus(
                order: $order,
                status: $request->previous_status,
                description: 'Permintaan pembatalan ditolak oleh admin.',
                adminId: $admin->id,
            );

            return $order->fresh([
                'cancellationRequest',
                'payment',
                'items.product',
            ]);
        });
    }

    private function rejectCancellationRequest(
        OrderCancelRequest $request,
        Admin $admin,
        ?string $notes
    ): void {

        $request->update([
            'status' => 'rejected',
            'approved_by' => $admin->id,
            'admin_notes' => $notes,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Pending Order
    |--------------------------------------------------------------------------
    */

    public function cancelPending(Order $order): Order
    {
        return DB::transaction(function () use ($order) {

            /*
            |--------------------------------------------------------------------------
            | Lock Order
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new RuntimeException(
                    'Order tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate
            |--------------------------------------------------------------------------
            */

            $this->validatePendingCancellation($order);

            /*
            |--------------------------------------------------------------------------
            | Load Relations
            |--------------------------------------------------------------------------
            */

            $order->loadMissing([
                'payment',
                'refund',
                'items.product',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Restore Inventory
            |--------------------------------------------------------------------------
            */

            $this->restoreInventory($order);

            /*
            |--------------------------------------------------------------------------
            | Cancel Order
            |--------------------------------------------------------------------------
            */

            $this->workflowService->changeStatus(
                order: $order,
                status: 'cancelled',
                description: 'Order cancelled before payment.',
                adminId: null,
            );

            return $order->fresh([
                'payment',
                'refund',
                'items.product',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Cancel Order
    |--------------------------------------------------------------------------
    */

    public function cancelByAdmin(
        Order $order,
        Admin $admin,
        ?string $reason = null,
    ): Order {

        return DB::transaction(function () use (
            $order,
            $admin,
            $reason
        ) {

            /*
            |--------------------------------------------------------------------------
            | Lock Order
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new RuntimeException(
                    'Order tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate
            |--------------------------------------------------------------------------
            */

            $this->validateAdminCancellation($order);

            /*
            |--------------------------------------------------------------------------
            | Load Relations
            |--------------------------------------------------------------------------
            */

            $order->loadMissing([
                'customer',
                'items.product',
                'payment',
                'refund',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Pending Order
            |--------------------------------------------------------------------------
            |
            | Belum dibayar → tidak perlu refund.
            |
            */

            if ($order->status === 'pending') {

                $this->restoreInventory($order);

            } else {

                /*
                |--------------------------------------------------------------------------
                | Paid / Processing
                |--------------------------------------------------------------------------
                |
                | Sudah dibayar → payment wajib valid untuk refund.
                |
                */

                if (! $order->payment) {
                    throw new RuntimeException(
                        'Payment untuk order tidak ditemukan.'
                    );
                }

                if (! $this->paymentService->isPaid($order->payment)) {
                    throw new RuntimeException(
                        'Payment belum berada pada status yang dapat direfund.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Restore Inventory
                |--------------------------------------------------------------------------
                */

                $this->restoreInventory($order);

                /*
                |--------------------------------------------------------------------------
                | Create Refund
                |--------------------------------------------------------------------------
                */

                $this->createRefundRequest($order);
            }

            /*
            |--------------------------------------------------------------------------
            | Cancel Order
            |--------------------------------------------------------------------------
            */

            $description = $reason
                ? "Order dibatalkan admin: {$reason}"
                : 'Order dibatalkan oleh admin.';

            $this->workflowService->changeStatus(
                order: $order,
                status: 'cancelled',
                description: $description,
                adminId: $admin->id,
            );

            return $order->fresh([
                'customer',
                'items.product',
                'payment',
                'refund',
                'cancellationRequest',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validateCustomerRequest(
        Order $order
    ): void {

        if (! in_array(
            $order->status,
            [
                'paid',
                'processing',
            ],
            true
        )) {
            throw new RuntimeException(
                'Order hanya dapat mengajukan pembatalan saat berstatus paid atau processing.'
            );
        }

        if (
            $order->cancellationRequest()
                ->where('status', 'pending')
                ->exists()
        ) {
            throw new RuntimeException(
                'Permintaan pembatalan untuk order ini masih menunggu persetujuan.'
            );
        }
    }

    private function validateApproval(
        OrderCancelRequest $request,
        Order $order
    ): void {

        if (! $request->isPending()) {
            throw new RuntimeException(
                'Hanya permintaan pembatalan pending yang dapat disetujui.'
            );
        }

        if ($order->status !== 'req_cancel') {
            throw new RuntimeException(
                'Order tidak sedang dalam proses pembatalan.'
            );
        }

        if (! in_array(
            $request->previous_status,
            [
                'paid',
                'processing',
            ],
            true
        )) {
            throw new RuntimeException(
                'Status sebelumnya tidak valid untuk pembatalan.'
            );
        }
    }

    private function validateRejection(
        OrderCancelRequest $request,
        Order $order
    ): void {

        if (! $request->isPending()) {
            throw new RuntimeException(
                'Hanya permintaan pembatalan pending yang dapat ditolak.'
            );
        }

        if ($order->status !== 'req_cancel') {
            throw new RuntimeException(
                'Order tidak sedang dalam proses pembatalan.'
            );
        }

        if (! in_array(
            $request->previous_status,
            [
                'paid',
                'processing',
            ],
            true
        )) {
            throw new RuntimeException(
                'Status sebelumnya tidak valid untuk pembatalan.'
            );
        }
    }

    private function validatePendingCancellation(
        Order $order
    ): void {

        if ($order->status !== 'pending') {
            throw new RuntimeException(
                'Hanya order pending yang dapat dibatalkan langsung.'
            );
        }

        if ($order->payment_status !== 'pending') {
            throw new RuntimeException(
                'Order sudah memiliki status pembayaran yang tidak sesuai.'
            );
        }

        if ($order->cancellationRequest()->exists()) {
            throw new RuntimeException(
                'Order sudah memiliki permintaan pembatalan.'
            );
        }
    }

    private function validateAdminCancellation(
        Order $order
    ): void {

        if (! in_array(
            $order->status,
            [
                'pending',
                'paid',
                'processing',
            ],
            true
        )) {
            throw new RuntimeException(
                'Order tidak dapat dibatalkan langsung oleh admin pada status saat ini.'
            );
        }

        if (
            $order->cancellationRequest()
                ->where('status', 'pending')
                ->exists()
        ) {
            throw new RuntimeException(
                'Order memiliki permintaan pembatalan yang masih menunggu proses.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Refund
    |--------------------------------------------------------------------------
    */

    private function createRefundRequest(
        Order $order
    ): void {

        if (! $order->payment) {
            throw new RuntimeException(
                'Payment untuk refund tidak ditemukan.'
            );
        }

        if (! $this->paymentService->isPaid($order->payment)) {
            throw new RuntimeException(
                'Payment belum berada pada status capture atau settlement.'
            );
        }

        if ($order->refund()->exists()) {
            throw new RuntimeException(
                'Refund untuk order ini sudah pernah dibuat.'
            );
        }

        $this->refundService->create($order);
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory
    |--------------------------------------------------------------------------
    */

    private function restoreInventory(
        Order $order
    ): void {

        $this->inventoryService->increaseStock(
            $this->buildInventoryCollection($order)
        );
    }

    private function buildInventoryCollection(
        Order $order
    ): Collection {

        return $order->items;
    }
}