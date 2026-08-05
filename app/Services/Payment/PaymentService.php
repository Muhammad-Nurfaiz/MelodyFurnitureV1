<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentService
{
    /*
    |--------------------------------------------------------------------------
    | Create Payment
    |--------------------------------------------------------------------------
    */

    public function create(Order $order, array $midtrans): Payment {
        if (empty($midtrans['snap_token'])) {
            throw new RuntimeException('Snap token dari Midtrans tidak ditemukan.');
        }

        if (empty($midtrans['expiry_time'])) {
            throw new RuntimeException('Expiry time dari Midtrans tidak ditemukan.');
        }

        return Payment::create([
            'order_id' => $order->id,
            'transaction_id' => $midtrans['transaction_id'] ?? null,
            'snap_token' => $midtrans['snap_token'],
            'payment_type' => null,
            'bank' => null,
            'va_number' => null,
            'gross_amount' => $order->total_payment,
            'expired_at' => $midtrans['expiry_time'],
            'transaction_status' => 'pending',
            'raw_response' => $midtrans,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Update
    |--------------------------------------------------------------------------
    */

    public function markPending(Payment $payment, array $notification = []): Payment {
        if ($this->isPending($payment)) {
            return $payment;
        }

        if ($this->isFinalized($payment)) {
            $this->logIgnoredTransition($payment, 'pending');
            return $payment;
        }
        return $this->updateStatus(
            $payment,
            [
                'transaction_status' => 'pending',
                'raw_notification' => $notification,
            ]
        );
    }

    public function markPaid(Payment $payment, array $notification): Payment {
        $status = $notification['transaction_status'] ?? null;

        if (! in_array( $status,['capture', 'settlement'],true)) {
            throw new RuntimeException('Status payment tidak valid untuk markPaid.');
        }

        /*
        |--------------------------------------------------------------------------
        | Ignore duplicate / backward notification
        |--------------------------------------------------------------------------
        */

        if ($payment->transaction_status === $status) {
            return $payment;
        }

        /*
        |--------------------------------------------------------------------------
        | Settlement setelah capture diperbolehkan
        |--------------------------------------------------------------------------
        */

        if ($payment->transaction_status === 'capture' && $status === 'settlement') {
            return $this->updatePaidStatus(
                $payment,
                $notification
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Jangan hidupkan kembali payment yang sudah final
        |--------------------------------------------------------------------------
        */

        if ($this->isFinalized($payment)) {
            $this->logIgnoredTransition(
                $payment,
                $status
            );

            return $payment;
        }

        return $this->updatePaidStatus(
            $payment,
            $notification
        );
    }

    public function markExpired(Payment $payment, array $notification = []): Payment {
        if ($this->isExpired($payment)) {
            return $payment;
        }

        if ($this->isFinalized($payment)) {
            $this->logIgnoredTransition($payment, 'expire');
            return $payment;
        }
        return $this->updateStatus(
            $payment,
            [
                'transaction_status' => 'expire',
                'raw_notification' => $notification,
            ]
        );
    }

    public function markCancelled(Payment $payment, array $notification = []): Payment {
        if ($this->isCancelled($payment)) {
            return $payment;
        }

        if ($this->isFinalized($payment)) {
            $this->logIgnoredTransition(
                $payment,
                'cancel'
            );

            return $payment;
        }

        return $this->updateStatus(
            $payment,
            [
                'transaction_status' => 'cancel',
                'raw_notification' => $notification,
            ]
        );
    }

    public function markFailed(Payment $payment, array $notification = []): Payment {
        if ($this->isFailed($payment)) {
            return $payment;
        }

        if ($this->isFinalized($payment)) {
            $this->logIgnoredTransition($payment, 'deny');

            return $payment;
        }

        return $this->updateStatus(
            $payment,
            [
                'transaction_status' => 'deny',
                'raw_notification' => $notification,
            ]
        );
    }

    public function markRefunded(Payment $payment,?array $notification = null): Payment {
        if ($this->isRefunded($payment)) {
            return $payment;
        }

        if (! $this->isPaid($payment)) {
            throw new RuntimeException('Payment harus berada pada status capture atau settlement sebelum direfund.');
        }

        $attributes = ['transaction_status' => 'refunded'];

        if ($notification !== null) {
            $attributes['raw_notification'] = $notification;
        }

        $payment->update($attributes);

        return $payment->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Midtrans Notification
    |--------------------------------------------------------------------------
    */

    public function updateFromNotification(Payment $payment, array $notification): Payment {
        $status = $notification['transaction_status'] ?? null;

        if ($status === null) {
            Log::warning(
                'Midtrans notification tanpa transaction_status',
                [
                    'payment_id' => $payment->id,
                    'notification' => $notification,
                ]
            );

            return $payment;
        }

        return match ($status) {
            'pending' =>
                $this->markPending(
                    $payment,
                    $notification
                ),

            'capture',
            'settlement' =>
                $this->markPaid(
                    $payment,
                    $notification
                ),

            'expire' =>
                $this->markExpired(
                    $payment,
                    $notification
                ),

            'cancel' =>
                $this->markCancelled(
                    $payment,
                    $notification
                ),

            'deny' =>
                $this->markFailed(
                    $payment,
                    $notification
                ),

            'refund',
            'partial_refund' =>
                $this->markRefunded(
                    $payment,
                    $notification
                ),

            default => $this->ignoreUnknownStatus(
                $payment,
                $status,
                $notification
            ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Finder
    |--------------------------------------------------------------------------
    */

    public function findByOrder(Order $order): ?Payment {
        return Payment::where('order_id', $order->id)->first();
    }

    public function findByTransaction(string $transactionId): ?Payment {
        return Payment::where('transaction_id', $transactionId)->first();
    }

    public function findByOrderNumber(string $orderId): ?Payment {
        return Payment::whereHas('order', function ($q) use ($orderId) {
            $q->where('midtrans_order_id', $orderId);
        })->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Checker
    |--------------------------------------------------------------------------
    */

    public function isPending(Payment $payment): bool {
        return $payment->transaction_status === 'pending';
    }

    public function isPaid(Payment $payment): bool {
        return in_array(
            $payment->transaction_status,
            [
                'capture',
                'settlement',
            ],
            true
        );
    }

    public function isExpired(Payment $payment): bool {
        return $payment->transaction_status === 'expire';
    }

    public function isCancelled(Payment $payment): bool {
        return $payment->transaction_status === 'cancel';
    }

    public function isFailed(Payment $payment): bool {
        return $payment->transaction_status === 'deny';
    }

    public function isRefunded(Payment $payment): bool {
        return in_array(
            $payment->transaction_status,
            [
                'refund',
                'partial_refund',
                'refunded',
            ],
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helper
    |--------------------------------------------------------------------------
    */

    private function updatePaidStatus(Payment $payment,array $notification): Payment {
        $transactionId = $notification['transaction_id'] ?? null;
        $grossAmount = $notification['gross_amount'] ?? null;

        if (empty($transactionId) || $grossAmount === null) {
            throw new RuntimeException('Notification payment tidak memiliki transaction_id atau gross_amount.');
        }

        return $this->updateStatus(
            $payment,
            [
                'transaction_id' => $transactionId,
                'transaction_status' => $notification['transaction_status'],
                'fraud_status' => $notification['fraud_status'] ?? null,
                'gross_amount' => (float) $grossAmount,
                'paid_at' => $payment->paid_at ?? now(),
                'payment_type' => $notification['payment_type'] ?? null,
                'bank' => $this->extractPaymentChannel($notification),
                'va_number' => $this->extractVaNumber($notification),
                'raw_notification' => $notification,
            ]
        );
    }

    private function updateStatus(Payment $payment,array $attributes): Payment {
        $payment->update($attributes);
        return $payment->fresh();
    }

    private function extractPaymentChannel(array $notification): ?string {
        if (isset($notification['va_numbers'][0]['bank'])) {
            return $notification['va_numbers'][0]['bank'];
        }
        return $notification['payment_type'] ?? null;
    }

    private function extractVaNumber(array $notification): ?string {
        if (isset($notification['va_numbers'][0]['va_number'])) {
            return $notification['va_numbers'][0]['va_number'];
        }
        if (isset($notification['permata_va_number'])) {
            return $notification['permata_va_number'];
        }
        return null;
    }

    private function isFinalized(Payment $payment): bool {
        return in_array(
            $payment->transaction_status,
            [
                'settlement',
                'refund',
                'partial_refund',
                'refunded',
            ],
            true
        );
    }

    private function logIgnoredTransition(Payment $payment,string $incomingStatus): void {
        Log::warning(
            'Ignored invalid payment status transition',
            [
                'payment_id' => $payment->id,
                'order_id' => $payment->order?->order_number,
                'current_status' => $payment->transaction_status,
                'incoming_status' => $incomingStatus,
            ]
        );
    }

    private function ignoreUnknownStatus(Payment $payment,string $status,array $notification): Payment {
        Log::warning(
            'Unknown Midtrans transaction status',
            [
                'payment_id' => $payment->id,
                'order_id' => $payment->order?->order_number,
                'status' => $status,
                'notification' => $notification,
            ]
        );
        return $payment;
    }
}