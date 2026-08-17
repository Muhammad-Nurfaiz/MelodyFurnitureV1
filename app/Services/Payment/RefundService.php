<?php

namespace App\Services\Payment;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Refund;
use App\Services\Voucher\VoucherService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RefundService
{
    public function __construct(
        protected RefundNumberService $numberService,
        protected PaymentService $paymentService,
        protected VoucherService $voucherService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Create Refund
    |--------------------------------------------------------------------------
    */

    public function create(Order $order): Refund {
        return DB::transaction(function () use ($order) {
            $order->loadMissing([
                'payment',
                'refund',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Validate Existing Refund
            |--------------------------------------------------------------------------
            */

            if ($order->refund) {
                throw new RuntimeException('Refund sudah pernah dibuat untuk order ini.');
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Payment
            |--------------------------------------------------------------------------
            */

            if (! $order->payment) {
                throw new RuntimeException('Order belum memiliki payment.');
            }

            if (! $this->paymentService->isPaid($order->payment)) {
                throw new RuntimeException('Payment belum berada pada status yang dapat direfund.');
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Amount
            |--------------------------------------------------------------------------
            */

            $amount = (float) $order->total_payment;
            $grossAmount = (float) $order->payment->gross_amount;

            if ($amount <= 0) {
                throw new RuntimeException('Jumlah refund tidak valid.');
            }

            if ($amount > $grossAmount) {
                throw new RuntimeException('Jumlah refund melebihi jumlah payment.');
            }

            /*
            |--------------------------------------------------------------------------
            | Create Refund
            |--------------------------------------------------------------------------
            */

            return Refund::create([
                'refund_number' => $this->numberService->generate(),
                'order_id' => $order->id,
                'payment_id' => $order->payment->id,
                'amount' => $amount,
                'status' => 'pending',
                'requested_at' => now(),
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Start Refund Processing
    |--------------------------------------------------------------------------
    */

    public function start(Refund $refund, Admin $admin): Refund {

        return DB::transaction(function () use ($refund, $admin) {

            /*
            |--------------------------------------------------------------------------
            | Reload Fresh Data
            |--------------------------------------------------------------------------
            */

            $refund = Refund::query()
                ->lockForUpdate()
                ->with([
                    'payment',
                    'order',
                ])
                ->findOrFail($refund->id);

            /*
            |--------------------------------------------------------------------------
            | Validate Refund Status
            |--------------------------------------------------------------------------
            */

            if (! $refund->isPending()) {
                throw new RuntimeException('Hanya refund dengan status pending yang dapat diproses.');
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Payment
            |--------------------------------------------------------------------------
            */

            if (! $refund->payment) {
                throw new RuntimeException('Payment untuk refund tidak ditemukan.');
            }

            if (! $this->paymentService->isPaid($refund->payment)) {
                throw new RuntimeException('Payment tidak berada pada status yang dapat direfund.');
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Refund Amount
            |--------------------------------------------------------------------------
            */

            if ((float) $refund->amount <= 0) {
                throw new RuntimeException('Jumlah refund tidak valid.');
            }

            if ((float) $refund->amount > (float) $refund->payment->gross_amount) {
                throw new RuntimeException('Jumlah refund melebihi jumlah payment.');
            }

            /*
            |--------------------------------------------------------------------------
            | Start Processing
            |--------------------------------------------------------------------------
            */

            $refund->update([
                'status' => 'processing',
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            return $refund->fresh([
                'order',
                'payment',
                'processor',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Refund
    |--------------------------------------------------------------------------
    */

    public function complete(Refund $refund, Admin $admin, ?string $notes = null): Refund {

        return DB::transaction(function () use ($refund,$admin,$notes) {

            /*
            |--------------------------------------------------------------------------
            | Reload With Lock
            |--------------------------------------------------------------------------
            */

            $refund = Refund::query()
                ->lockForUpdate()
                ->with([
                    'payment',
                    'order',
                ])
                ->findOrFail($refund->id);

            /*
            |--------------------------------------------------------------------------
            | Validate Refund Status
            |--------------------------------------------------------------------------
            */

            if (! $refund->isProcessing()) {
                throw new RuntimeException('Refund hanya dapat diselesaikan dari status processing.');
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Payment
            |--------------------------------------------------------------------------
            */

            if (! $refund->payment) {
                throw new RuntimeException('Payment untuk refund tidak ditemukan.');
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Refund Amount
            |--------------------------------------------------------------------------
            */

            if ((float) $refund->amount <= 0) {
                throw new RuntimeException('Jumlah refund tidak valid.');
            }

            if ((float) $refund->amount > (float) $refund->payment->gross_amount) {
                throw new RuntimeException('Jumlah refund melebihi jumlah payment.');
            }

            /*
            |--------------------------------------------------------------------------
            | Complete Refund
            |--------------------------------------------------------------------------
            */

            $refund->update([
                'status' => 'completed',
                'processed_by' => $admin->id,
                'notes' => $notes,
                'completed_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Mark Payment Refunded
            |--------------------------------------------------------------------------
            */

            if (! $this->paymentService->isRefunded($refund->payment)) {
                $this->paymentService->markRefunded($refund->payment);
            }

            $refund->order->loadMissing('voucher');

            if ($refund->order->voucher) {
                $this->voucherService->releaseUsage(
                    $refund->order->voucher
                );
            }

            return $refund->fresh([
                'order',
                'payment',
                'processor',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Refund
    |--------------------------------------------------------------------------
    */

    public function reject(Refund $refund, Admin $admin, string $notes): Refund {

        if (blank(trim($notes))) {
            throw new RuntimeException('Alasan penolakan refund wajib diisi.');
        }

        return DB::transaction(function () use ($refund,$admin,$notes) {

            /*
            |--------------------------------------------------------------------------
            | Reload With Lock
            |--------------------------------------------------------------------------
            */

            $refund = Refund::query()->lockForUpdate()->findOrFail($refund->id);

            /*
            |--------------------------------------------------------------------------
            | Validate Status
            |--------------------------------------------------------------------------
            */

            if (! $refund->isPending()) {
                throw new RuntimeException('Refund hanya dapat ditolak dari status pending.');
            }

            /*
            |--------------------------------------------------------------------------
            | Reject Refund
            |--------------------------------------------------------------------------
            */

            $refund->update([
                'status' => 'rejected',
                'processed_by' => $admin->id,
                'notes' => trim($notes),
                'processed_at' => now(),
            ]);

            return $refund->fresh([
                'order',
                'payment',
                'processor',
            ]);
        });
    }
}