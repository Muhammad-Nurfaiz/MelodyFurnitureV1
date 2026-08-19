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

        /*
        |--------------------------------------------------------------------------
        | Logo Melody Furniture
        |--------------------------------------------------------------------------
        */

        $logoPath = public_path('images/output.png');

        abort_unless(
            file_exists($logoPath),
            500,
            'Logo packing label tidak ditemukan.'
        );

        $logoBase64 = 'data:image/png;base64,' . base64_encode(
            file_get_contents($logoPath)
        );


        /*
        |--------------------------------------------------------------------------
        | Fragile Image
        |--------------------------------------------------------------------------
        */

        $fragilePath = public_path('images/fragile.png');

        abort_unless(
            file_exists($fragilePath),
            500,
            'Gambar fragile tidak ditemukan.'
        );

        $fragileBase64 = 'data:image/png;base64,' . base64_encode(
            file_get_contents($fragilePath)
        );


        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        return Pdf::loadView(
            'documents.packing-label.order',
            [
                'order' => $order,

                'logoBase64' =>
                    $logoBase64,

                'fragileBase64' =>
                    $fragileBase64,
            ]
        )
        ->setPaper('a5', 'portrait')
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