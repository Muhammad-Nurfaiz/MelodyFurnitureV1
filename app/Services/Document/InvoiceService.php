<?php

namespace App\Services\Document;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceService
{
    /**
     * Load seluruh data yang dibutuhkan invoice.
     */
    public function prepare(Order $order): Order
    {
        return $order->load([
            'items',
            'payment',
            'voucher',
        ]);
    }

    /**
     * Generate PDF invoice.
     */
    public function pdf(Order $order)
    {
        $order = $this->prepare($order);

        return Pdf::loadView(
            'documents.invoice.order',
            [
                'order' => $order,
            ]
        )->setPaper('a4', 'portrait');
    }

    /**
     * Download invoice.
     */
    public function download(Order $order)
    {
        $pdf = $this->pdf($order);

        return $pdf->download(
            "invoice-{$order->order_number}.pdf"
        );
    }

    /**
     * Stream invoice untuk print / preview browser.
     */
    public function stream(Order $order)
    {
        $pdf = $this->pdf($order);

        return $pdf->stream(
            "invoice-{$order->order_number}.pdf"
        );
    }
}