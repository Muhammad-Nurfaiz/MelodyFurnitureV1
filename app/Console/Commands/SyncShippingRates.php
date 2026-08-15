<?php

namespace App\Console\Commands;

use App\Models\ShippingCourier;
use App\Models\ShippingRate;
use Illuminate\Console\Command;
use MadeByClowd\Nusantara\Models\Regency;

class SyncShippingRates extends Command
{
    protected $signature = 'shipping:sync-rates';

    protected $description =
        'Melengkapi shipping rate untuk semua courier aktif dan regency';

    public function handle(): int
    {
        $couriers = ShippingCourier::query()
            ->where('is_active', true)
            ->get();

        $regencies = Regency::query()
            ->select('id')
            ->orderBy('id')
            ->get();

        $created = 0;
        $existing = 0;

        foreach ($couriers as $courier) {

            $rateType = match ($courier->code) {

                'jnt_cargo' =>
                    'per_kg',

                'sentral_cargo' =>
                    'tiered',

                default =>
                    null,
            };

            if (!$rateType) {

                $this->warn(
                    "Courier {$courier->code} dilewati karena rate type belum dikonfigurasi."
                );

                continue;
            }

            foreach ($regencies as $regency) {

                $rate = ShippingRate::query()
                    ->where('courier_id', $courier->id)
                    ->where('regency_id', $regency->id)
                    ->first();

                if ($rate) {
                    $existing++;
                    continue;
                }

                ShippingRate::create([
                    'courier_id' => $courier->id,
                    'regency_id' => $regency->id,

                    'rate_type' => $rateType,

                    'price_per_kg' => null,
                    'first_price' => null,
                    'additional_price_per_kg' => null,

                    'is_active' => true,
                ]);

                $created++;
            }
        }

        $this->newLine();

        $this->info(
            "Shipping rate berhasil disinkronisasi."
        );

        $this->line(
            "Record existing : {$existing}"
        );

        $this->line(
            "Record created  : {$created}"
        );

        return self::SUCCESS;
    }
}