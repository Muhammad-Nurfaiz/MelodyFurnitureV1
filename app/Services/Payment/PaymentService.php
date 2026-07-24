<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /*
    |--------------------------------------------------------------------------
    | Create Payment
    |--------------------------------------------------------------------------
    */

    public function create(
        Order $order,
        array $midtrans
    ): Payment {

        return Payment::create([

            'order_id'          => $order->id,

            'transaction_id'    => $midtrans['transaction_id'],

            'snap_token'        => $midtrans['snap_token'],

            'payment_type'      => null,

            'payment_channel'   => null,

            'va_number'         => null,

            'expiry_time'       => $midtrans['expiry_time'],

            'status'            => 'pending',

            'raw_response'      => $midtrans,

        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | Status Update
    |--------------------------------------------------------------------------
    */

    public function markPending(
        Payment $payment,
        array $notification = []
    ): Payment {

        return $this->updateStatus(

            $payment,

            [

                'status' => 'pending',

                'raw_notification' => $notification,

            ]

        );

    }

    public function markPaid(
        Payment $payment,
        array $notification
    ): Payment {

        return $this->updateStatus(

            $payment,

            [

                'status' => 'paid',

                'paid_at' => now(),

                'payment_type'
                    => $notification['payment_type'] ?? null,

                'payment_channel'
                    => $this->extractPaymentChannel($notification),

                'va_number'
                    => $this->extractVaNumber($notification),

                'raw_notification'
                    => $notification,

            ]

        );

    }

    public function markExpired(
        Payment $payment,
        array $notification = []
    ): Payment {

        return $this->updateStatus(

            $payment,

            [

                'status' => 'expired',

                'raw_notification' => $notification,

            ]

        );

    }

    public function markCancelled(
        Payment $payment,
        array $notification = []
    ): Payment {

        return $this->updateStatus(

            $payment,

            [

                'status' => 'cancelled',

                'raw_notification' => $notification,

            ]

        );

    }

    public function markFailed(
        Payment $payment,
        array $notification = []
    ): Payment {

        return $this->updateStatus(

            $payment,

            [

                'status' => 'failed',

                'raw_notification' => $notification,

            ]

        );

    }

    public function markRefunded(
        Payment $payment,
        ?array $payload = null
    ): Payment {

        $payment->update([

            'status' => 'refunded',

            'paid_at' => $payment->paid_at,

            'raw_response' => $payload
                ? json_encode($payload)
                : $payment->raw_response,

        ]);

        return $payment->fresh();

    }

    /*
    |--------------------------------------------------------------------------
    | Midtrans Notification
    |--------------------------------------------------------------------------
    */

    public function updateFromNotification(
        Payment $payment,
        array $notification
    ): Payment {

        return match ($notification['transaction_status']) {

            'pending'
                => $this->markPending(
                    $payment,
                    $notification
                ),

            'capture',

            'settlement'
                => $this->markPaid(
                    $payment,
                    $notification
                ),

            'expire'
                => $this->markExpired(
                    $payment,
                    $notification
                ),

            'cancel'
                => $this->markCancelled(
                    $payment,
                    $notification
                ),

            'deny'
                => $this->markFailed(
                    $payment,
                    $notification
                ),

            default => $payment,

        };

    }

    /*
    |--------------------------------------------------------------------------
    | Finder
    |--------------------------------------------------------------------------
    */

    public function findByOrder(
        Order $order
    ): ?Payment {

        return Payment::where(

            'order_id',

            $order->id

        )->first();

    }

    public function findByTransaction(
        string $transactionId
    ): ?Payment {

        return Payment::where(

            'transaction_id',

            $transactionId

        )->first();

    }

    /*
    |--------------------------------------------------------------------------
    | Checker
    |--------------------------------------------------------------------------
    */

    public function isPending(
        Payment $payment
    ): bool {

        return $payment->status === 'pending';

    }

    public function isPaid(
        Payment $payment
    ): bool {

        return $payment->status === 'paid';

    }

    public function isExpired(
        Payment $payment
    ): bool {

        return $payment->status === 'expired';

    }

    public function isCancelled(
        Payment $payment
    ): bool {

        return $payment->status === 'cancelled';

    }

    public function isFailed(
        Payment $payment
    ): bool {

        return $payment->status === 'failed';

    }

    /*
    |--------------------------------------------------------------------------
    | Private Helper
    |--------------------------------------------------------------------------
    */

    private function updateStatus(
        Payment $payment,
        array $attributes
    ): Payment {

        DB::transaction(function () use (

            $payment,
            $attributes

        ) {

            $payment->update($attributes);

        });

        return $payment->fresh();

    }

    private function extractPaymentChannel(
        array $notification
    ): ?string {

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