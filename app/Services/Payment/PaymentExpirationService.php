<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentExpirationService
{
    public function __construct(
        protected MidtransService $midtransService,
        protected PaymentService $paymentService,
        protected OrderService $orderService,
    ) {}

    /**
     * Memproses seluruh payment yang telah melewati expiry_time.
     */
    public function processExpiredPayments(): int
    {
        $payments = Payment::query()
            ->with('order')
            ->where('status', 'pending')
            ->where('expiry_time', '<=', now())
            ->get();

        $processed = 0;

        foreach ($payments as $payment) {

            try {

                /*
                |--------------------------------------------------------------------------
                | Ambil status terbaru dari Midtrans
                |--------------------------------------------------------------------------
                */

                $status = $this->midtransService
                    ->status($payment->transaction_id);

                $transactionStatus =
                    $status->transaction_status ?? null;

                DB::transaction(function () use (
                    $payment,
                    $status,
                    $transactionStatus
                ) {

                    switch ($transactionStatus) {

                        /*
                        |--------------------------------------------------------------------------
                        | Customer sudah berhasil membayar
                        |--------------------------------------------------------------------------
                        */

                        case 'settlement':

                        case 'capture':

                            $this->paymentService
                                ->markPaid(
                                    $payment,
                                    (array) $status
                                );

                            $this->orderService
                                ->markPaid(
                                    $payment->order
                                );

                            break;

                        /*
                        |--------------------------------------------------------------------------
                        | Masih pending tetapi sudah lewat 10 menit
                        |--------------------------------------------------------------------------
                        */

                        case 'pending':

                            $this->midtransService
                                ->expire(
                                    $payment->transaction_id
                                );

                            $this->paymentService
                                ->markExpired(
                                    $payment,
                                    (array) $status
                                );

                            $this->orderService
                                ->expireOrder(
                                    $payment->order
                                );

                            break;

                        /*
                        |--------------------------------------------------------------------------
                        | Midtrans sudah expire / cancel
                        |--------------------------------------------------------------------------
                        */

                        case 'expire':

                        case 'cancel':

                            $this->paymentService
                                ->markExpired(
                                    $payment,
                                    (array) $status
                                );

                            $this->orderService
                                ->expireOrder(
                                    $payment->order
                                );

                            break;

                        /*
                        |--------------------------------------------------------------------------
                        | Status lain diabaikan
                        |--------------------------------------------------------------------------
                        */

                        default:

                            break;

                    }

                });

                $processed++;

            } catch (Throwable $e) {

                report($e);

                continue;

            }

        }

        return $processed;
    }
}