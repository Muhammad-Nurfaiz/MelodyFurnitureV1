<?php

namespace App\Http\Controllers\Admin\Document;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Document\InvoiceService;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {
    }

    /**
     * Preview / print invoice.
     */
    public function show(Order $order)
    {
        abort_unless(
            $order->canDownloadInvoice(),
            404
        );

        return $this->invoiceService->stream($order);
    }

    /**
     * Download invoice PDF.
     */
    public function download(Order $order)
    {
        abort_unless(
            $order->canDownloadInvoice(),
            404
        );

        return $this->invoiceService->download($order);
    }
}