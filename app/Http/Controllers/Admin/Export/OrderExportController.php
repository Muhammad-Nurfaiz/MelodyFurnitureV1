<?php

namespace App\Http\Controllers\Admin\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\ExportOrderRequest;
use App\Services\Export\OrderExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderExportController extends Controller
{
    public function __construct(
        protected OrderExportService $orderExportService,
    ) {}

    /**
     * Export orders.
     */
    public function export(
        ExportOrderRequest $request
    ): BinaryFileResponse {

        $data = $request->validated();

        $result = $this->orderExportService->export(

            startDate: $data['start_date'],

            endDate: $data['end_date'],

            status: $data['status'] ?? 'all',

            paymentStatus:
                $data['payment_status'] ?? 'all',
        );

        return response()
            ->download(
                $result['path'],
                $result['filename']
            )
            ->deleteFileAfterSend(true);
    }
}