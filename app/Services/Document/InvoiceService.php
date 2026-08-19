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

        $logoPath = public_path('images/output.png');

        if (! file_exists($logoPath)) {
            throw new \RuntimeException(
                "Logo invoice tidak ditemukan: {$logoPath}"
            );
        }

        $logoBase64 = base64_encode(
            file_get_contents($logoPath)
        );
        
        return Pdf::loadView(
            'documents.invoice.order',
            [
                'order' => $order,
                'logoBase64' => $logoBase64,
            ]
        )
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'chroot' => [
                    public_path(),
                    base_path(),
                ],
            ]);
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