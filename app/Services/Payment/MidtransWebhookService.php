<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MidtransWebhookService
{
    private const SYSTEM = 'system';
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
        Log::info('Webhook received', $notification);
        logger()->info('Incoming Midtrans Notification',$notification);
        $this->verifySignature($notification);
        $payment = $this->paymentService->findByOrderNumber($notification['order_id']);
        if (!$payment) {
            throw new RuntimeException('Payment tidak ditemukan.');
        }
        DB::transaction(function () use ($payment,$notification) {
            $this->processStatus($payment,$notification);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Process Status
    |--------------------------------------------------------------------------
    */

    private function processStatus(Payment $payment,array $notification): void {
        $status = $notification['transaction_status'] ?? '';
        Log::info('Webhook status',['status' => $status,]);
        if ($status === 'capture') {
            if (($notification['fraud_status'] ?? '') === 'accept') {
                $this->processPaid($payment,$notification);
            }
            return;
        }

        match ($status) {
            'settlement' => $this->processPaid($payment,$notification),
            'pending' => $this->processPending($payment,$notification),
            'expire' => $this->processExpired($payment,$notification),
            'cancel' => $this->processCancelled($payment,$notification),
            'deny' => $this->processDenied($payment,$notification),
            'refund' => $this->processRefund($payment, $notification),
            default => logger()->warning('Unknown Midtrans status',$notification),
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
        | Payment Idempotent
        |--------------------------------------------------------------------------
        */

        if (! $this->paymentService->isPaid($payment)) {
            $payment = $this->paymentService->markPaid($payment, $notification);
        }

        /*
        |--------------------------------------------------------------------------
        | Order Idempotent
        |--------------------------------------------------------------------------
        */

        if ($payment->order->status !== 'paid') {
            $this->orderService->markPaid($payment->order);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Pending
    |--------------------------------------------------------------------------
    */

    private function processPending(Payment $payment,array $notification): void {
        if ($this->paymentService->isPending($payment)) {
            logger()->info('Duplicate pending notification ignored',['order' => $payment->order->order_number]);
            return;
        }
        $this->paymentService->markPending($payment, $notification);
        logger()->info('Payment marked pending', ['order' => $payment->order->order_number]);
    }

    /*
    |--------------------------------------------------------------------------
    | Expired
    |--------------------------------------------------------------------------
    */

    private function processExpired(Payment $payment,array $notification): void {
        if ($this->paymentService->isExpired($payment)) {
            logger()->info('Duplicate expire notification ignored', ['order' => $payment->order->order_number,]);
            return;
        }
        $this->paymentService->markExpired($payment,$notification);
        $this->orderService->expireOrder($payment->order);
        logger()->info('Payment expired', ['order' => $payment->order->order_number]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelled
    |--------------------------------------------------------------------------
    */

    private function processCancelled(Payment $payment,array $notification): void {
        if ($this->paymentService->isCancelled($payment)) {
            logger()->info('Duplicate cancel notification ignored', ['order' => $payment->order->order_number,]);
            return;
        }
        $this->paymentService->markCancelled($payment,$notification);
        $this->orderService->cancelOrder($payment->order,self::REASON_CANCELLED,self::SYSTEM);
        logger()->info('Payment cancelled', ['order' => $payment->order->order_number]);
    }

    /*
    |--------------------------------------------------------------------------
    | Failed
    |--------------------------------------------------------------------------
    */

    private function processDenied(Payment $payment,array $notification): void {
        if ($this->paymentService->isFailed($payment)) {
            logger()->info('Duplicate deny notification ignored', ['order' => $payment->order->order_number,]);
            return;
        }
        $this->paymentService->markFailed($payment,$notification);
        $this->orderService->cancelOrder($payment->order,self::REASON_DENIED,self::SYSTEM);
        logger()->info('Payment denied', ['order' => $payment->order->order_number,]);
    }

    private function processRefund(Payment $payment,array $notification): void {
        if ($this->paymentService->isRefunded($payment)) {
            logger()->info('Duplicate refund notification ignored', ['order' => $payment->order->order_number,]);
            return;
        }
        $this->paymentService->markRefunded($payment,$notification);
        logger()->info('Payment refunded', ['order' => $payment->order->order_number,]);
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

        $generated = hash(
            'sha512',
            $orderId .
            $statusCode .
            $grossAmount .
            config('midtrans.server_key')
        );

        if (!hash_equals($generated,$signature)) {
            throw new RuntimeException('Invalid Midtrans signature.');
        }
    }
}