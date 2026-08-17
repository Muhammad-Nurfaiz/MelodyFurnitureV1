<?php

namespace App\Services\Document;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class PackingLabelService
{
    /**
     * Generate packing label PDF.
     */
    public function generate(Order $order)
    {
        $order->loadMissing([
            'items',
        ]);

        return Pdf::loadView(
            'documents.packing-label.order',
            [
                'order' => $order,
            ]
        )
        ->setPaper('a5', 'portrait');
    }

    /**
     * Download packing label PDF.
     */
    public function download(Order $order)
    {
        $pdf = $this->generate($order);

        return $pdf->download(
            'packing-label-' . $order->order_number . '.pdf'
        );
    }

    /**
     * Stream packing label PDF.
     */
    public function stream(Order $order)
    {
        $pdf = $this->generate($order);

        return $pdf->stream(
            'packing-label-' . $order->order_number . '.pdf'
        );
    }
}