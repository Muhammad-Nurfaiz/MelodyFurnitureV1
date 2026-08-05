<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MidtransWebhookService
{
    private const REASON_CANCELLED = 'Cancelled by Midtrans';
    private const REASON_DENIED = 'Payment denied';

    public function __construct(
        protected PaymentService $paymentService,
        protected OrderService $orderService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Handle Webhook
    |--------------------------------------------------------------------------
    */

    public function handle(array $notification): void
    {
        Log::info('Midtrans webhook received', [
            'order_id' => $notification['order_id'] ?? null,
            'transaction_id' => $notification['transaction_id'] ?? null,
            'transaction_status' => $notification['transaction_status'] ?? null,
            'payment_type' => $notification['payment_type'] ?? null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Verify Signature
        |--------------------------------------------------------------------------
        */

        $this->verifySignature($notification);

        /*
        |--------------------------------------------------------------------------
        | Validate Required Data
        |--------------------------------------------------------------------------
        */

        $orderId = $notification['order_id'] ?? null;

        if (empty($orderId)) {
            throw new RuntimeException('Midtrans notification tidak memiliki order_id.');
        }

        /*
        |--------------------------------------------------------------------------
        | Find Payment
        |--------------------------------------------------------------------------
        */

        $payment = $this->paymentService->findByOrderNumber($orderId);

        if (! $payment) {
            throw new RuntimeException('Payment tidak ditemukan.');
        }

        /*
        |--------------------------------------------------------------------------
        | Process
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($payment, $notification) {
            /*
            |--------------------------------------------------------------------------
            | Lock Payment
            |--------------------------------------------------------------------------
            |
            | Mencegah dua webhook yang datang bersamaan memproses payment
            | secara tidak konsisten.
            |
            */

            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw new RuntimeException('Payment tidak ditemukan saat processing webhook.');
            }

            $this->processStatus($payment,$notification);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Process Status
    |--------------------------------------------------------------------------
    */

    private function processStatus(Payment $payment,array $notification): void {

        $status = $notification['transaction_status'] ?? null;

        if ($status === null) {
            Log::warning('Midtrans webhook tanpa transaction_status',[
                    'payment_id' => $payment->id,
                    'notification' => $notification,
            ]);
            return;
        }

        Log::info('Processing Midtrans webhook status', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order?->order_number,
            'current_status' => $payment->transaction_status,
            'incoming_status' => $status,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Capture
        |--------------------------------------------------------------------------
        */

        if ($status === 'capture') {

            /*
            |--------------------------------------------------------------------------
            | Capture hanya dianggap paid jika fraud_status accept
            |--------------------------------------------------------------------------
            */

            if (($notification['fraud_status'] ?? null) !== 'accept') {

                Log::warning('Capture notification ignored because fraud status is not accept',[
                        'payment_id' => $payment->id,
                        'fraud_status' =>$notification['fraud_status'] ?? null,
                ]);
                return;
            }

            $this->processPaid($payment,$notification);
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Other Status
        |--------------------------------------------------------------------------
        */

        match ($status) {
            'settlement' => $this->processPaid($payment,$notification),
            'pending' => $this->processPending($payment,$notification),
            'expire' => $this->processExpired($payment,$notification),
            'cancel' => $this->processCancelled($payment,$notification),
            'deny' => $this->processDenied($payment,$notification),
            'refund',
            'partial_refund' => $this->processRefund($payment,$notification),
            default => $this->processUnknown($payment,$status,$notification),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Paid
    |--------------------------------------------------------------------------
    */

    private function processPaid(Payment $payment,array $notification): void {

        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        */

        $previousStatus = $payment->transaction_status;

        $payment = $this->paymentService->markPaid($payment,$notification);

        /*
        |--------------------------------------------------------------------------
        | Ignore if payment transition was rejected
        |--------------------------------------------------------------------------
        */

        if (! $this->paymentService->isPaid($payment)) {

            Log::warning('Payment paid transition was not applied',[
                    'payment_id' => $payment->id,
                    'previous_status' => $previousStatus,
                    'incoming_status' => $notification['transaction_status'] ?? null,
                    'current_status' => $payment->transaction_status,
            ]);
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */

        $order = $payment->order;

        if (! $order) {
            throw new RuntimeException('Order untuk payment tidak ditemukan.');
        }

        /*
        |--------------------------------------------------------------------------
        | Only pending order can be marked paid
        |--------------------------------------------------------------------------
        */

        if ($order->status === 'pending') {

            $this->orderService->markPaid($order,null);

            Log::info('Order marked as paid from Midtrans webhook',[
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_id' => $payment->id,
            ]);
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Already processed order
        |--------------------------------------------------------------------------
        */

        Log::info('Order payment transition skipped',[
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_status' => $order->status,
                'payment_status' => $payment->transaction_status,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Pending
    |--------------------------------------------------------------------------
    */

    private function processPending(Payment $payment,array $notification): void {

        if ($this->paymentService->isPending($payment)) {

            Log::info('Duplicate pending notification ignored',[
                    'payment_id' => $payment->id,
                    'order' => $payment->order?->order_number,
            ]);

            return;
        }

        $previousStatus = $payment->transaction_status;

        $payment = $this->paymentService->markPending($payment,$notification);

        /*
        |--------------------------------------------------------------------------
        | Check whether transition was actually applied
        |--------------------------------------------------------------------------
        */

        if (! $this->paymentService->isPending($payment)) {

            Log::warning('Pending transition ignored',[
                    'payment_id' => $payment->id,
                    'previous_status' => $previousStatus,
                    'current_status' => $payment->transaction_status,
            ]);
            return;
        }

        Log::info('Payment marked pending',[
                'payment_id' => $payment->id,
                'order' => $payment->order?->order_number,]);
    }

    /*
    |--------------------------------------------------------------------------
    | Expired
    |--------------------------------------------------------------------------
    */

    private function processExpired(Payment $payment,array $notification): void {

        if ($this->paymentService->isExpired($payment)) {

            Log::info('Duplicate expire notification ignored',[
                    'payment_id' => $payment->id,
                    'order' => $payment->order?->order_number,
            ]);
            return;
        }

        $previousStatus = $payment->transaction_status;

        $payment = $this->paymentService->markExpired($payment,$notification);

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | Jangan expire Order jika PaymentService menolak transition.
        |
        */

        if (! $this->paymentService->isExpired($payment)) {

            Log::warning('Expire transition ignored',[
                    'payment_id' => $payment->id,
                    'previous_status' => $previousStatus,
                    'current_status' => $payment->transaction_status,
            ]);
            return;
        }

        $order = $payment->order;

        if (! $order) {
            throw new RuntimeException('Order untuk payment tidak ditemukan.');
        }

        /*
        |--------------------------------------------------------------------------
        | Expire Order
        |--------------------------------------------------------------------------
        */

        if ($order->status === 'pending') {

            $this->orderService->expireOrder($order);

            Log::info('Order expired from Midtrans webhook',[
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);
            return;
        }

        Log::info('Order expiration skipped',[
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_status' => $order->status,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelled
    |--------------------------------------------------------------------------
    */

    private function processCancelled(Payment $payment,array $notification): void {

        if ($this->paymentService->isCancelled($payment)) {

            Log::info('Duplicate cancel notification ignored',[
                    'payment_id' => $payment->id,
                    'order' => $payment->order?->order_number,
                ]);
            return;
        }

        $previousStatus = $payment->transaction_status;

        $payment = $this->paymentService->markCancelled($payment,$notification);

        /*
        |--------------------------------------------------------------------------
        | Check transition
        |--------------------------------------------------------------------------
        */

        if (! $this->paymentService->isCancelled($payment)) {

            Log::warning('Cancel transition ignored',[
                    'payment_id' => $payment->id,
                    'previous_status' => $previousStatus,
                    'current_status' => $payment->transaction_status,
                ]);

            return;
        }

        $order = $payment->order;

        if (! $order) {
            throw new RuntimeException('Order untuk payment tidak ditemukan.');
        }

        /*
        |--------------------------------------------------------------------------
        | Cancel only appropriate orders
        |--------------------------------------------------------------------------
        */

        if ($order->canBeCancelled()) {

            $this->orderService->cancelOrder($order,self::REASON_CANCELLED);

            Log::info('Order cancelled from Midtrans webhook',[
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]
            );
            return;
        }

        Log::info('Order cancellation skipped',[
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_status' => $order->status,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Denied
    |--------------------------------------------------------------------------
    */

    private function processDenied(Payment $payment,array $notification): void {

        if ($this->paymentService->isFailed($payment)) {

            Log::info('Duplicate deny notification ignored',[
                    'payment_id' => $payment->id,
                    'order' => $payment->order?->order_number,
                ]);
            return;
        }

        $previousStatus = $payment->transaction_status;

        $payment = $this->paymentService->markFailed($payment,$notification);

        /*
        |--------------------------------------------------------------------------
        | Check transition
        |--------------------------------------------------------------------------
        */

        if (! $this->paymentService->isFailed($payment)) {

            Log::warning('Deny transition ignored',[
                    'payment_id' => $payment->id,
                    'previous_status' => $previousStatus,
                    'current_status' => $payment->transaction_status,
            ]);
            return;
        }

        $order = $payment->order;

        if (! $order) {
            throw new RuntimeException('Order untuk payment tidak ditemukan.');
        }

        if ($order->canBeCancelled()) {

            $this->orderService->cancelOrder($order,self::REASON_DENIED);

            Log::info('Order cancelled because payment was denied',[
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);

            return;
        }

        Log::info('Order cancellation after deny skipped',[
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_status' => $order->status,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Refund
    |--------------------------------------------------------------------------
    */

    private function processRefund(Payment $payment,array $notification): void {

        $incomingStatus = $notification['transaction_status'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Duplicate Refund
        |--------------------------------------------------------------------------
        */

        if ($this->paymentService->isRefunded($payment)) {

            Log::info('Duplicate refund notification ignored',[
                    'payment_id' => $payment->id,
                    'order' => $payment->order?->order_number,
                    'incoming_status' => $incomingStatus,
                ]);
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Payment must currently be paid
        |--------------------------------------------------------------------------
        */

        if (! $this->paymentService->isPaid($payment)) {

            Log::warning('Refund notification ignored because payment is not paid',[
                'payment_id' => $payment->id,
                'current_status' => $payment->transaction_status,
                'incoming_status' => $incomingStatus,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Mark Payment Refunded
        |--------------------------------------------------------------------------
        */

        $payment = $this->paymentService->markRefunded($payment,$notification);

        Log::info('Payment marked as refunded from Midtrans webhook',[
                'payment_id' => $payment->id,
                'order' => $payment->order?->order_number,
                'incoming_status' => $incomingStatus,
                'refund_amount' => $notification['refund_amount'] ?? null,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Unknown Status
    |--------------------------------------------------------------------------
    */

    private function processUnknown(Payment $payment, string $status, array $notification): void {

        Log::warning('Unknown Midtrans transaction status',[
                'payment_id' => $payment->id,
                'order_id' => $payment->order?->id,
                'order_number' => $payment->order?->order_number,
                'status' => $status,
                'notification' => $notification,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Signature
    |--------------------------------------------------------------------------
    */

    private function verifySignature(array $notification): void {

        $signature = $notification['signature_key'] ?? '';

        $orderId = $notification['order_id'] ?? '';
        $statusCode = $notification['status_code'] ?? '';
        $grossAmount = $notification['gross_amount'] ?? '';

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signature === '') {
            throw new RuntimeException('Data signature Midtrans tidak lengkap.');
        }

        $serverKey = config('midtrans.server_key');

        if (empty($serverKey)) {
            throw new RuntimeException('Midtrans server key belum dikonfigurasi.');
        }

        $generated = hash(
            'sha512',
            $orderId .
            $statusCode .
            $grossAmount .
            $serverKey
        );

        if (! hash_equals($generated, $signature)) {
            throw new RuntimeException('Invalid Midtrans signature.');
        }
    }
}