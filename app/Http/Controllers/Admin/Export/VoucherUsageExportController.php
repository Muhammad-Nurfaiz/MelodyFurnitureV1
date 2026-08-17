<?php

namespace App\Http\Controllers\Admin\Export;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\Export\VoucherUsageExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VoucherUsageExportController extends Controller
{
    public function __construct(
        protected VoucherUsageExportService $voucherUsageExportService,
    ) {}

    /**
     * Export voucher usage history to XLSX.
     */
    public function export(
        Voucher $voucher
    ): BinaryFileResponse {

        $result = $this->voucherUsageExportService->export(
            $voucher
        );

        return response()
            ->download(
                $result['path'],
                $result['filename']
            )
            ->deleteFileAfterSend(true);
    }
}