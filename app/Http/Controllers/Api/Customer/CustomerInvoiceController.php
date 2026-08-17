<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Document\InvoiceService;
use Illuminate\Http\Request;
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
        Request $request,
        Order $order
    ): Response {
        /*
        |--------------------------------------------------------------------------
        | Get Customer From Session Middleware
        |--------------------------------------------------------------------------
        */

        $customer = $request->attributes->get('customer');

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer session tidak ditemukan.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Authorization
        |--------------------------------------------------------------------------
        |
        | Customer hanya boleh mengakses invoice miliknya sendiri.
        |
        */

        if ($order->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice tidak ditemukan.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Invoice Availability
        |--------------------------------------------------------------------------
        */

        if (!$order->canDownloadInvoice()) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice belum tersedia untuk pesanan ini.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Download PDF
        |--------------------------------------------------------------------------
        */

        return $this->invoiceService->download($order);
    }
}