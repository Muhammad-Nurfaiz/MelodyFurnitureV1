<?php

namespace App\Console\Commands;

use App\Services\Shipping\ShippingRateImportService;
use Illuminate\Console\Command;
use RuntimeException;

class ImportJntShippingRates extends Command
{
    protected $signature = 'shipping:import-jnt
                            {file : Path ke file XLSX J&T Cargo}';

    protected $description = 'Import price list J&T Cargo ke shipping rates';

    public function handle(
        ShippingRateImportService $service
    ): int {

        $file = $this->argument('file');

        if (!is_file($file)) {

            $this->error(
                "File tidak ditemukan: {$file}"
            );

            return self::FAILURE;
        }

        $this->info(
            'Memulai import price list J&T Cargo...'
        );

        try {

            $count = $service->importJnt($file);

            $this->newLine();

            $this->info(
                "Import berhasil. {$count} tarif berhasil diproses."
            );

            return self::SUCCESS;

        } catch (RuntimeException $e) {

            $this->newLine();

            $this->error(
                'Import dibatalkan.'
            );

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}