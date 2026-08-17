<?php

namespace App\Services\Export;

use App\Models\Order;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OrderExportService
{
    /**
     * Export orders to XLSX.
     */
    public function export(
        string $startDate,
        string $endDate,
        ?string $status = null,
        ?string $paymentStatus = null,
    ): array {
        $orders = $this->query(
            startDate: $startDate,
            endDate: $endDate,
            status: $status,
            paymentStatus: $paymentStatus,
        )->get();

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Orders');

        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $headers = [
            'No. Order',
            'Tanggal',
            'Customer',
            'Email',
            'Telepon',
            'Subtotal Produk',
            'Diskon Voucher',
            'Ongkir Asli',
            'Ongkir',
            'Total Pembayaran',
            'Voucher',
            'Payment Status',
            'Order Status',
            'Courier',
            'Service',
            'Tracking Number',
            'Berat (gram)',
            'Alamat',
            'Kota',
            'Provinsi',
            'Kode Pos',
            'Tanggal Bayar',
        ];

        $sheet->fromArray(
            $headers,
            null,
            'A1'
        );

        /*
        |--------------------------------------------------------------------------
        | Header Style
        |--------------------------------------------------------------------------
        */

        $headerRange = 'A1:V1';

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

        $rowNumber = 2;

        foreach ($orders as $order) {

            $address = $order->shipping_address ?? [];

            $sheet->fromArray([

                $order->order_number,

                optional($order->created_at)
                    ->format('Y-m-d H:i:s'),

                $order->customer_name,

                $order->customer_email,

                $order->customer_phone,

                (float) $order->total_product_price,

                (float) $order->voucher_discount_amount,

                (float) $order->original_shipping_fee,

                (float) $order->shipping_fee,

                (float) $order->total_payment,

                $order->voucher?->code ?? '-',

                $order->payment_status,

                $order->status,

                $order->courier ?? '-',

                $order->shipping_method,

                $order->tracking_number ?? '-',

                (float) $order->total_weight,

                $address['address'] ?? '-',

                $address['city'] ?? '-',

                $address['province'] ?? '-',

                $address['postal_code'] ?? '-',

                optional($order->paid_at)
                    ->format('Y-m-d H:i:s'),

            ], null, "A{$rowNumber}");

            $rowNumber++;
        }

        /*
        |--------------------------------------------------------------------------
        | Currency Formatting
        |--------------------------------------------------------------------------
        */

        if ($rowNumber > 2) {

            $sheet
                ->getStyle("F2:J" . ($rowNumber - 1))
                ->getNumberFormat()
                ->setFormatCode('#,##0');

        }

        /*
        |--------------------------------------------------------------------------
        | Borders
        |--------------------------------------------------------------------------
        */

        if ($rowNumber > 2) {

            $sheet
                ->getStyle("A1:V" . ($rowNumber - 1))
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
            ->getStyle("A1:V" . max(1, $rowNumber - 1))
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        /*
        |--------------------------------------------------------------------------
        | Freeze Header
        |--------------------------------------------------------------------------
        */

        $sheet->freezePane('A2');

        /*
        |--------------------------------------------------------------------------
        | Auto Width
        |--------------------------------------------------------------------------
        */

        foreach (
            range(
                'A',
                'V'
            ) as $column
        ) {

            $sheet
                ->getColumnDimension($column)
                ->setAutoSize(true);

        }

        /*
        |--------------------------------------------------------------------------
        | Limit overly wide columns
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getColumnDimension('R')
            ->setWidth(40);

        $sheet
            ->getColumnDimension('C')
            ->setWidth(25);

        $sheet
            ->getColumnDimension('D')
            ->setWidth(30);

        /*
        |--------------------------------------------------------------------------
        | Filename
        |--------------------------------------------------------------------------
        */

        $filename =
            'orders-' .
            $startDate .
            '-to-' .
            $endDate .
            '.xlsx';

        /*
        |--------------------------------------------------------------------------
        | Writer
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

        $writer = new Xlsx($spreadsheet);

        $writer->save($path);

        return [
            'path' => $path,
            'filename' => $filename,
            'count' => $orders->count(),
        ];
    }

    /**
     * Build order query.
     */
    protected function query(
        string $startDate,
        string $endDate,
        ?string $status = null,
        ?string $paymentStatus = null,
    ) {
        return Order::query()

            ->with([
                'voucher',
            ])

            ->whereBetween(
                'created_at',
                [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59',
                ]
            )

            ->when(
                $status &&
                $status !== 'all',
                fn ($query) =>
                    $query->where(
                        'status',
                        $status
                    )
            )

            ->when(
                $paymentStatus &&
                $paymentStatus !== 'all',
                fn ($query) =>
                    $query->where(
                        'payment_status',
                        $paymentStatus
                    )
            )

            ->orderBy(
                'created_at'
            );
    }
}