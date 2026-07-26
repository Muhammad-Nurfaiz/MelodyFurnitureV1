<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\OrderCancelRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Services\Payment\RefundService;
use App\Services\Payment\PaymentService;
use App\Services\Inventory\ProductInventoryService;
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

    public function requestByCustomer(Order $order,string $reason): OrderCancelRequest {
        $this->validateCustomerRequest($order);
        return DB::transaction(function () use ($order,$reason) {
            $request = $this->createCancellationRequest($order,$reason);
            $this->cancelWorkflow($order);
            return $request;
        });
    }

    private function createCancellationRequest(Order $order,string $reason): OrderCancelRequest {
        return OrderCancelRequest::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'reason' => $reason,
            'previous_status' => $order->status,
            'status' => 'pending',
        ]);
    }

    private function cancelWorkflow(Order $order): void {
        $this->workflowService->changeStatus($order,'req_cancel','Customer requested cancellation.',null);
    }

    public function approve(OrderCancelRequest $request,Admin $admin,?string $notes = null): Order {
        $this->validateApproval($request);
        return DB::transaction(function () use ($request,$admin,$notes) {
            $this->approveCancellationRequest($request,$admin,$notes);
            $order = $request->order;
            $order->loadMissing(['payment','refund','items.product',]);
            $this->restoreInventory($order);
            $this->processRefund($order);
            $this->workflowService->changeStatus($order,'cancelled','Cancellation approved.',$admin->name);
            return $order->fresh(['payment','refund','items.product',]);
        });
    }

    private function approveCancellationRequest(OrderCancelRequest $request,Admin $admin,?string $notes): void {
        $request->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
            'admin_notes' => $notes,
            'approved_at' => now(),
        ]);
    }

    private function restoreInventory(Order $order): void {
        $this->inventoryService->increaseStock($this->buildInventoryCollection($order));
    }

    private function buildInventoryCollection(Order $order): Collection {
        return $order->items->map(fn ($item) => ['product' => $item->product,'qty' => $item->quantity,]);
    }

    private function processRefund(Order $order): void {
        if (! $order->payment) {
            return;
        }

        if (! $this->paymentService->isPaid($order->payment)) {
            return;
        }

        $this->paymentService->markRefunded($order->payment);

        if (! $order->refund()->exists()) {
            $this->refundService->create($order);
        }
    }

    public function reject(OrderCancelRequest $request,Admin $admin,?string $notes = null): Order {
        $this->validateRejection($request);
        return DB::transaction(function () use ($request,$admin,$notes) {
            $this->rejectCancellationRequest($request,$admin,$notes);
            $order = $request->order;
            $this->workflowService->changeStatus($order,$request->previous_status,'Cancellation rejected.',$admin->name);
            return $order->fresh();
        });
    }

    private function validateRejection(OrderCancelRequest $request): void {
        if (! $request->isPending()) {
            throw new RuntimeException('Hanya permintaan pending yang dapat ditolak.');
        }
    }

    private function validateApproval(OrderCancelRequest $request): void {
        if ($request->status !== 'pending') {
            throw new RuntimeException('Hanya permintaan pending yang bisa disetujui.');
        }
    }

    private function validateCustomerRequest(Order $order): void {
        if (! in_array($order->status,['pending','paid','processing',])) {
            throw new RuntimeException('Order tidak dapat dibatalkan.');
        }

        if ($order->cancellationRequest()->where('status', 'pending')->exists()) {
            throw new RuntimeException('Permintaan pembatalan sudah pernah dibuat.');
        }
    }

    private function rejectCancellationRequest(OrderCancelRequest $request,Admin $admin,?string $notes): void {
        $request->update([
            'status' => 'rejected',
            'approved_by' => $admin->id,
            'admin_notes' => $notes,
            'approved_at' => now(),
        ]);
    }
}