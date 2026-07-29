<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /*
    |--------------------------------------------------------------------------
    | Create Payment
    |--------------------------------------------------------------------------
    */

    public function create(Order $order, array $midtrans): Payment {
        return Payment::create([
            'order_id'           => $order->id,
            'transaction_id'     => $midtrans['transaction_id'] ?? null,
            'snap_token'         => $midtrans['snap_token'],
            'payment_type'       => null,
            'bank'               => null,
            'va_number'          => null,
            'gross_amount'       => $order->total_payment,
            'expired_at'         => $midtrans['expiry_time'],
            'transaction_status' => 'pending',
            'raw_response'       => $midtrans,
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
        return $this->updateStatus(
            $payment,
            [
                'transaction_status' => 'pending',
                'raw_notification' => $notification,
            ]
        );
    }

    public function markPaid(Payment $payment, array $notification): Payment {
        if ($this->isPaid($payment)) {
            return $payment;
        }
        return $this->updateStatus(
            $payment,
            [
                'transaction_id'    => $notification['transaction_id'],
                'transaction_status'=> $notification['transaction_status'],
                'fraud_status'      => $notification['fraud_status'] ?? null,
                'gross_amount' => (float) $notification['gross_amount'],
                'paid_at'           => now(),
                'payment_type'      => $notification['payment_type'] ?? null,
                'bank'              => $this->extractPaymentChannel($notification),
                'va_number'         => $this->extractVaNumber($notification),
                'raw_notification'  => $notification,
            ]
        );
        Log::info('Payment updated');
    }

    public function markExpired(Payment $payment, array $notification = []): Payment {
        if ($this->isExpired($payment)) {
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
        return $this->updateStatus(
            $payment,
            [
                'transaction_status' => 'deny',
                'raw_notification' => $notification,
            ]
        );
    }

    public function markRefunded(Payment $payment, ?array $payload = null): Payment {
        if ($this->isRefunded($payment)) {
            return $payment;
        }
        $payment->update([
            'transaction_status' => 'refunded',
            'paid_at' => $payment->paid_at,
            'raw_response' => $payload ?? $payment->raw_response,
        ]);
        return $payment->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Midtrans Notification
    |--------------------------------------------------------------------------
    */

    public function updateFromNotification(Payment $payment,array $notification): Payment {
        return match ($notification['transaction_status']) {
            'pending' => $this->markPending($payment,$notification),
            'capture',
            'settlement' => $this->markPaid($payment,$notification),
            'expire' => $this->markExpired($payment,$notification),
            'cancel' => $this->markCancelled($payment,$notification),
            'deny' => $this->markFailed($payment,$notification),
            default => $payment,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Finder
    |--------------------------------------------------------------------------
    */

    public function findByOrder(Order $order): ?Payment {
        return Payment::where('order_id',$order->id)->first();
    }

    public function findByTransaction(string $transactionId): ?Payment {
        return Payment::where('transaction_id',$transactionId)->first();
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

    public function isRefunded(Payment $payment): bool
    {
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

    private function updateStatus(Payment $payment, array $attributes): Payment {
        $payment->update($attributes);
        return $payment->fresh();
    }

    private function extractPaymentChannel(array $notification): ?string {
        if (isset($notification['va_numbers'][0]['bank'])) {
            return $notification['va_numbers'][0]['bank'];
        }
        return $notification['payment_type'] ?? null;
    }

    private function extractVaNumber(
        array $notification
    ): ?string {
        if (isset($notification['va_numbers'][0]['va_number'])) {
            return $notification['va_numbers'][0]['va_number'];
        }
        if (isset($notification['permata_va_number'])) {
            return $notification['permata_va_number'];
        }
        return null;
    }
}