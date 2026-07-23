<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\DB;
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
        $this->validatePayload($notification);

        $this->verifySignature($notification);

        $payment = $this->paymentService->findByTransaction(
            $notification['transaction_id']
        );

        if (!$payment) {
            throw new RuntimeException(
                'Payment tidak ditemukan.'
            );
        }

        DB::transaction(function () use (
            $payment,
            $notification
        ) {
            $this->processStatus(
                $payment,
                $notification
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Process Status
    |--------------------------------------------------------------------------
    */

    private function processStatus(
        Payment $payment,
        array $notification
    ): void {

        match ($notification['transaction_status']) {

            'capture',
            'settlement'
                => $this->processPaid(
                    $payment,
                    $notification
                ),

            'pending'
                => $this->processPending(
                    $payment,
                    $notification
                ),

            'expire'
                => $this->processExpired(
                    $payment,
                    $notification
                ),

            'cancel'
                => $this->processCancelled(
                    $payment,
                    $notification
                ),

            'deny'
                => $this->processDenied(
                    $payment,
                    $notification
                ),

            default => null,

        };

    }

    /*
    |--------------------------------------------------------------------------
    | Paid
    |--------------------------------------------------------------------------
    */

    private function processPaid(
        Payment $payment,
        array $notification
    ): void {

        if (
            $this->paymentService->isPaid($payment)
        ) {
            return;
        }

        $this->paymentService
            ->markPaid(
                $payment,
                $notification
            );

        $this->orderService
            ->markPaid(
                $payment->order
            );

    }

    /*
    |--------------------------------------------------------------------------
    | Pending
    |--------------------------------------------------------------------------
    */

    private function processPending(
        Payment $payment,
        array $notification
    ): void {

        if (
            $this->paymentService->isPending($payment)
        ) {
            return;
        }

        $this->paymentService
            ->markPending(
                $payment,
                $notification
            );

    }

    /*
    |--------------------------------------------------------------------------
    | Expired
    |--------------------------------------------------------------------------
    */

    private function processExpired(
        Payment $payment,
        array $notification
    ): void {

        if (
            $this->paymentService->isExpired($payment)
        ) {
            return;
        }

        $this->paymentService
            ->markExpired(
                $payment,
                $notification
            );

        $this->orderService
            ->expireOrder(
                $payment->order
            );

    }

    /*
    |--------------------------------------------------------------------------
    | Cancelled
    |--------------------------------------------------------------------------
    */

    private function processCancelled(
        Payment $payment,
        array $notification
    ): void {

        if (
            $this->paymentService->isCancelled($payment)
        ) {
            return;
        }

        $this->paymentService
            ->markCancelled(
                $payment,
                $notification
            );

        $this->orderService
            ->cancelOrder(
                $payment->order,
                self::REASON_CANCELLED,
                self::SYSTEM
            );

    }

    /*
    |--------------------------------------------------------------------------
    | Failed
    |--------------------------------------------------------------------------
    */

    private function processDenied(
        Payment $payment,
        array $notification
    ): void {

        if (
            $this->paymentService->isFailed($payment)
        ) {
            return;
        }

        $this->paymentService
            ->markFailed(
                $payment,
                $notification
            );

        $this->orderService
            ->cancelOrder(
                $payment->order,
                self::REASON_DENIED,
                self::SYSTEM
            );

    }

    /*
    |--------------------------------------------------------------------------
    | Signature
    |--------------------------------------------------------------------------
    */

    private function verifySignature(
        array $notification
    ): void {

        $signature = $notification['signature_key'] ?? '';

        $generated = hash(
            'sha512',
            $notification['order_id']
            .
            $notification['status_code']
            .
            $notification['gross_amount']
            .
            config('midtrans.server_key')
        );

        if (
            !hash_equals(
                $generated,
                $signature
            )
        ) {
            throw new RuntimeException(
                'Invalid Midtrans signature.'
            );
        }

    }

    /*
    |--------------------------------------------------------------------------
    | Payload Validation
    |--------------------------------------------------------------------------
    */

    private function validatePayload(
        array $notification
    ): void {

        foreach ([
            'transaction_id',
            'transaction_status',
            'order_id',
            'status_code',
            'gross_amount',
            'signature_key',
        ] as $field) {

            if (
                empty($notification[$field])
            ) {
                throw new RuntimeException(
                    "{$field} is required."
                );
            }

        }

    }
}