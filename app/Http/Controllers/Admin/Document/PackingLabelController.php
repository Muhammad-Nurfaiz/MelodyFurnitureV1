<?php

namespace App\Http\Controllers\Admin\Document;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Document\PackingLabelService;

class PackingLabelController extends Controller
{
    public function __construct(
        protected PackingLabelService $packingLabelService,
    ) {}

    /**
     * Display packing label PDF.
     */
    public function show(Order $order)
    {
        abort_unless(
            $order->canDownloadPackingLabel(),
            403
        );

        return $this->packingLabelService->stream($order);
    }

    /**
     * Download packing label PDF.
     */
    public function download(Order $order)
    {
        abort_unless(
            $order->canDownloadPackingLabel(),
            403
        );

        return $this->packingLabelService->download($order);
    }
}