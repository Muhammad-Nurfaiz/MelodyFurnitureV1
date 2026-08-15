<?php

namespace App\Console\Commands;

use App\Services\Shipping\ShippingRateImportService;
use Illuminate\Console\Command;
use RuntimeException;

class ImportSentralShippingRates extends Command
{
    protected $signature = 'shipping:import-sentral
                            {file : Path ke file XLSX Sentral Cargo}';

    protected $description = 'Import price list Sentral Cargo ke shipping rates';

    public function handle(
        ShippingRateImportService $service
    ): int {

        $file = $this->argument('file');

        if (!is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        $this->info(
            'Memulai import price list Sentral Cargo...'
        );

        try {

            $result = $service->importSentral($file);

            $this->newLine();

            $this->info(
                "Import berhasil. {$result['imported']} tarif berhasil diproses."
            );

            if ($result['skipped'] > 0) {

                $this->newLine();

                $this->warn(
                    "{$result['skipped']} tarif dilewati karena berstatus \"Sesuai Sistem\"."
                );
            }

            return self::SUCCESS;

        } catch (RuntimeException $e) {

            $this->newLine();

            $this->error('Import dibatalkan.');

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}