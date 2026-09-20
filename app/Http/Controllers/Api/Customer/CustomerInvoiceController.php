<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Document\InvoiceService;
use Symfony\Component\HttpFoundation\Response;

class CustomerInvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
    ) {}

    /**
     * Download invoice PDF milik customer.
     */
    public function download(
        string $trackingToken
    ): Response {
        $order = Order::query()
            ->where('tracking_token', $trackingToken)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice tidak ditemukan.',
            ], 404);
        }

        if (! $order->canDownloadInvoice()) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice belum tersedia untuk pesanan ini.',
            ], 422);
        }

        return $this->invoiceService->download($order);
    }
}