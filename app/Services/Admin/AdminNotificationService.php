<?php

namespace App\Services\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Product;
use App\Models\OrderCancelRequest;
use App\Notifications\CancellationRejectedNotification;
use App\Notifications\CancellationApprovedNotification;
use App\Notifications\CancellationRequestNotification;
use App\Notifications\LowStockProductNotification;
use App\Notifications\RefundPendingNotification;
use App\Notifications\PendingOrderNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class AdminNotificationService
{
    /**
     * Batas stok yang dianggap menipis.
     */
    private const LOW_STOCK_THRESHOLD = 3;

    /**
     * Kirim notification pending order ke seluruh administrator.
     */
    public function notifyPendingOrder(Order $order): void
    {
        Admin::query()
            ->each(function (Admin $admin) use ($order) {

                $exists = $admin->notifications()
                    ->where('type', PendingOrderNotification::class)
                    ->whereJsonContains(
                        'data->type',
                        'pending_order'
                    )
                    ->whereJsonContains(
                        'data->order_id',
                        $order->id
                    )
                    ->exists();

                if (! $exists) {
                    $admin->notify(
                        new PendingOrderNotification($order)
                    );
                }
            });
    }

    /**
     * Kirim notification refund pending ke seluruh administrator.
     */
    public function notifyPendingRefund(Refund $refund): void
    {
        Admin::query()
            ->each(function (Admin $admin) use ($refund) {

                $exists = $admin->notifications()
                    ->where('type', RefundPendingNotification::class)
                    ->whereJsonContains(
                        'data->type',
                        'pending_refund'
                    )
                    ->whereJsonContains(
                        'data->refund_id',
                        $refund->id
                    )
                    ->exists();

                if (! $exists) {
                    $admin->notify(
                        new RefundPendingNotification($refund)
                    );
                }
            });
    }

    public function notifyLowStockProduct(Product $product): void
    {
        if ($product->ready_stock > self::LOW_STOCK_THRESHOLD) {
            return;
        }

        Admin::query()
            ->each(function (Admin $admin) use ($product) {

                $exists = $admin->notifications()
                    ->where('type', LowStockProductNotification::class)
                    ->whereJsonContains(
                        'data->type',
                        'low_stock'
                    )
                    ->whereJsonContains(
                        'data->product_id',
                        $product->id
                    )
                    ->whereNull('read_at')
                    ->exists();

                if (! $exists) {
                    $admin->notify(
                        new LowStockProductNotification($product)
                    );
                }
            });
    }

    /**
     * Kirim notification cancellation request
     * ke seluruh administrator.
     */
    public function notifyCancellationRequest(
        OrderCancelRequest $cancellationRequest
    ): void {
        $cancellationRequest->loadMissing([
            'order',
        ]);

        Admin::query()
            ->each(function (Admin $admin) use ($cancellationRequest) {

                $exists = $admin->notifications()
                    ->where(
                        'type',
                        CancellationRequestNotification::class
                    )
                    ->whereJsonContains(
                        'data->type',
                        'cancellation_request'
                    )
                    ->whereJsonContains(
                        'data->cancellation_request_id',
                        $cancellationRequest->id
                    )
                    ->whereNull('read_at')
                    ->exists();

                if (! $exists) {
                    $admin->notify(
                        new CancellationRequestNotification(
                            $cancellationRequest
                        )
                    );
                }
            });
    }

    /**
     * Kirim notification ketika cancellation request
     * disetujui oleh administrator.
     */
    public function notifyCancellationApproved(
        OrderCancelRequest $cancellationRequest
    ): void {
        $cancellationRequest->loadMissing([
            'order',
        ]);

        Admin::query()
            ->each(function (Admin $admin) use ($cancellationRequest) {

                $exists = $admin->notifications()
                    ->where(
                        'type',
                        CancellationApprovedNotification::class
                    )
                    ->whereJsonContains(
                        'data->type',
                        'cancellation_approved'
                    )
                    ->whereJsonContains(
                        'data->cancellation_request_id',
                        $cancellationRequest->id
                    )
                    ->whereNull('read_at')
                    ->exists();

                if (! $exists) {
                    $admin->notify(
                        new CancellationApprovedNotification(
                            $cancellationRequest
                        )
                    );
                }
            });
    }

    /**
     * Kirim notification ketika permintaan pembatalan ditolak.
     */
    public function notifyCancellationRejected(
        OrderCancelRequest $request
    ): void {
        Admin::query()
            ->each(function (Admin $admin) use ($request) {

                $exists = $admin->notifications()
                    ->where(
                        'type',
                        CancellationRejectedNotification::class
                    )
                    ->whereJsonContains(
                        'data->type',
                        'cancellation_rejected'
                    )
                    ->whereJsonContains(
                        'data->cancellation_request_id',
                        $request->id
                    )
                    ->whereNull('read_at')
                    ->exists();

                if (! $exists) {
                    $admin->notify(
                        new CancellationRejectedNotification($request)
                    );
                }
            });
    }

    /**
     * Ambil seluruh administrator.
     */
    public function admins(): Collection
    {
        return Admin::query()->get();
    }

    /**
     * Threshold stok rendah.
     */
    public function lowStockThreshold(): int
    {
        return self::LOW_STOCK_THRESHOLD;
    }
}