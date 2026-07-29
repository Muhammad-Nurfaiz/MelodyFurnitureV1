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
     * Memproses seluruh payment yang telah melewati batas waktu pembayaran.
     */
    public function processExpiredPayments(): int
    {
        $payments = Payment::query()
            ->with('order')
            ->where('transaction_status', 'pending')
            ->where('expired_at', '<=', now())
            ->get();

        $processed = 0;

        foreach ($payments as $payment) {

            /*
            |--------------------------------------------------------------------------
            | Pastikan Order Masih Ada
            |--------------------------------------------------------------------------
            */
            $order = $payment->order;

            if (!$order) {
                continue;
            }

            try {

                $status = $this->midtransService->status($order->midtrans_order_id);
                $transactionStatus = $status->transaction_status ?? null;
                $updated = false;
                DB::transaction(function () use ($payment,$order,$status,$transactionStatus,&$updated) {

                    switch ($transactionStatus) {

                        /*
                        |--------------------------------------------------------------------------
                        | Pembayaran Berhasil
                        |--------------------------------------------------------------------------
                        */
                        case 'settlement':
                        case 'capture':
                            $this->paymentService->markPaid($payment, (array) $status);
                            $this->orderService->markPaid($order);
                            $updated = true;
                            break;

                        case 'pending':
                            $this->midtransService->expire($order->midtrans_order_id);
                            $this->paymentService->markExpired($payment,['transaction_status' => 'expire']);
                            $this->orderService->expireOrder($order);
                            $updated = true;
                            break;

                        /*
                        |--------------------------------------------------------------------------
                        | Sudah Expire / Cancel di Midtrans
                        |--------------------------------------------------------------------------
                        */
                        case 'expire':
                        case 'cancel':
                            $this->paymentService->markExpired($payment, (array) $status);
                            $this->orderService->expireOrder($order);
                            $updated = true;
                            break;

                        default:
                            break;
                    }
                });
                if ($updated) {
                    $processed++;
                }
            } catch (Throwable $e) {
                if ($e->getCode() == 404) {
                    DB::transaction(function () use ($payment, $order) {
                        $this->paymentService->markExpired(
                            $payment,
                            ['transaction_status' => 'expire']
                        );
                        $this->orderService->expireOrder($order);
                    });
                    $processed++;
                    continue;
                }

                Log::error('Payment expiration failed', [
                    'order_number' => $order->order_number,
                    'message' => $e->getMessage(),
                ]);
            }
        }
        return $processed;
    }
}