<?php

namespace App\Services\Export;

use App\Models\Order;
use App\Models\Voucher;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class VoucherUsageExportService
{
    /**
     * Export voucher usage history to XLSX.
     */
    public function export(
        Voucher $voucher
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Usage Orders
        |--------------------------------------------------------------------------
        |
        | Hanya order yang benar-benar sudah dibayar.
        |
        */

        $orders = Order::query()

            ->where('voucher_id', $voucher->id)

            ->where(
                'payment_status',
                'paid'
            )

            ->orderBy(
                'created_at'
            )

            ->get();

        /*
        |--------------------------------------------------------------------------
        | Spreadsheet
        |--------------------------------------------------------------------------
        */

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Voucher Usage');

        /*
        |--------------------------------------------------------------------------
        | Voucher Information
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'A1',
            'Riwayat Penggunaan Voucher'
        );

        $sheet->setCellValue(
            'A2',
            'Kode Voucher'
        );

        $sheet->setCellValue(
            'B2',
            $voucher->code
        );

        $sheet->setCellValue(
            'A3',
            'Total Penggunaan'
        );

        $sheet->setCellValue(
            'B3',
            $orders->count()
        );

        $sheet->getStyle('A1:B3')->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet->getStyle('A1')->applyFromArray([

            'font' => [
                'bold' => true,
                'size' => 14,
            ],

        ]);

        $sheet->getStyle('A2:A3')->applyFromArray([

            'font' => [
                'bold' => true,
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $headers = [
            'No.',
            'Order',
            'Midtrans Order ID',
            'Pelanggan',
            'Email',
            'Total Belanja',
            'Diskon Voucher',
            'Total Pembayaran',
            'Status Order',
            'Payment Status',
            'Digunakan Pada',
        ];

        $sheet->fromArray(
            $headers,
            null,
            'A5'
        );

        /*
        |--------------------------------------------------------------------------
        | Header Style
        |--------------------------------------------------------------------------
        */

        $headerRange = 'A5:K5';

        $sheet->getStyle($headerRange)->applyFromArray([

            'font' => [
                'bold' => true,
                'color' => [
                    'rgb' => 'FFFFFF',
                ],
            ],

            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '2563EB',
                ],
            ],

            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],

            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'rgb' => 'D1D5DB',
                    ],
                ],
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Data
        |--------------------------------------------------------------------------
        */

        $rowNumber = 6;
        $number = 1;

        foreach ($orders as $order) {

            $sheet->fromArray([

                $number,

                $order->order_number,

                $order->midtrans_order_id ?? '-',

                $order->customer_name ?? '-',

                $order->customer_email ?? '-',

                (float) $order->total_product_price,

                (float) $order->voucher_discount_amount,

                (float) $order->total_payment,

                $order->status ?? '-',

                $order->payment_status ?? '-',

                optional($order->created_at)
                    ->format('Y-m-d H:i:s'),

            ], null, "A{$rowNumber}");

            $rowNumber++;
            $number++;
        }

        /*
        |--------------------------------------------------------------------------
        | Currency Formatting
        |--------------------------------------------------------------------------
        */

        if ($rowNumber > 6) {

            $sheet
                ->getStyle(
                    "F6:H" . ($rowNumber - 1)
                )
                ->getNumberFormat()
                ->setFormatCode('#,##0');

        }

        /*
        |--------------------------------------------------------------------------
        | Borders
        |--------------------------------------------------------------------------
        */

        if ($rowNumber > 6) {

            $sheet
                ->getStyle(
                    "A5:K" . ($rowNumber - 1)
                )
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(
                    Border::BORDER_THIN
                );

        }

        /*
        |--------------------------------------------------------------------------
        | Alignment
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle(
                "A5:K" . max(5, $rowNumber - 1)
            )
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        /*
        |--------------------------------------------------------------------------
        | Freeze Header
        |--------------------------------------------------------------------------
        */

        $sheet->freezePane('A6');

        /*
        |--------------------------------------------------------------------------
        | Auto Width
        |--------------------------------------------------------------------------
        */

        foreach (
            range('A', 'K') as $column
        ) {

            $sheet
                ->getColumnDimension($column)
                ->setAutoSize(true);

        }

        /*
        |--------------------------------------------------------------------------
        | Column Width
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getColumnDimension('D')
            ->setWidth(25);

        $sheet
            ->getColumnDimension('E')
            ->setWidth(30);

        $sheet
            ->getColumnDimension('C')
            ->setWidth(30);

        /*
        |--------------------------------------------------------------------------
        | Filename
        |--------------------------------------------------------------------------
        */

        $safeCode = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '-',
            $voucher->code
        );

        $filename =
            'voucher-' .
            strtolower($safeCode) .
            '-usage.xlsx';

        /*
        |--------------------------------------------------------------------------
        | Path
        |--------------------------------------------------------------------------
        */

        $path = storage_path(
            'app/temp/' . $filename
        );

        if (! is_dir(dirname($path))) {

            mkdir(
                dirname($path),
                0755,
                true
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Writer
        |--------------------------------------------------------------------------
        */

        $writer = new Xlsx(
            $spreadsheet
        );

        $writer->save($path);

        return [
            'path' => $path,
            'filename' => $filename,
            'count' => $orders->count(),
        ];
    }
}