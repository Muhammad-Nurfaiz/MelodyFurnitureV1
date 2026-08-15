<?php

namespace App\Services\Shipping;

use App\Models\ShippingRate;
use RuntimeException;

class ShippingService
{
    private const DEFAULT_SERVICE = 'regular';

    /*
    |--------------------------------------------------------------------------
    | Calculate Shipping
    |--------------------------------------------------------------------------
    */

    public function calculate(
        int|float $weight,
        string $regencyId,
        ?string $courier = null,
        ?string $service = null,
    ): float {

        $normalizedWeight =
            $this->normalizeWeight($weight);

        if ($normalizedWeight <= 0) {
            throw new RuntimeException(
                'Berat pengiriman harus lebih besar dari 0 kg.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Service
        |--------------------------------------------------------------------------
        |
        | Semua pengiriman perusahaan menggunakan regular.
        |
        */

        $service = self::DEFAULT_SERVICE;

        $rate = $this->getRate(
            regencyId: $regencyId,
            courier: $courier,
        );

        if (!$rate) {
            throw new RuntimeException(
                "Tarif pengiriman untuk regency {$regencyId} " .
                "dan courier " .
                ($courier ?? 'tidak ditentukan') .
                " tidak ditemukan."
            );
        }

        return match ($rate->rate_type) {

            'per_kg' =>
                $this->calculatePerKg(
                    $rate,
                    $normalizedWeight
                ),

            'tiered' =>
                $this->calculateTiered(
                    $rate,
                    $normalizedWeight
                ),

            default =>
                throw new RuntimeException(
                    "Tipe tarif '{$rate->rate_type}' tidak didukung."
                ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Rate
    |--------------------------------------------------------------------------
    */

    protected function getRate(
        string $regencyId,
        ?string $courier = null,
    ): ?ShippingRate {

        $query = ShippingRate::query()
            ->where('regency_id', $regencyId)
            ->where('is_active', true)
            ->with('courier');

        /*
        |--------------------------------------------------------------------------
        | Courier
        |--------------------------------------------------------------------------
        */

        if ($courier !== null && $courier !== '') {

            $query->whereHas(
                'courier',
                function ($q) use ($courier) {

                    $q->where('code', $courier)
                        ->orWhere('id', $courier);
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Service
        |--------------------------------------------------------------------------
        |
        | Tidak digunakan karena perusahaan hanya menyediakan
        | satu layanan yaitu regular.
        |
        */

        return $query->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Per KG
    |--------------------------------------------------------------------------
    */

    protected function calculatePerKg(
        ShippingRate $rate,
        int $weight
    ): float {

        if ($rate->price_per_kg === null) {
            throw new RuntimeException(
                "Tarif per_kg untuk regency {$rate->regency_id} " .
                "tidak memiliki price_per_kg."
            );
        }

        return round(
            $weight * (float) $rate->price_per_kg,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Tiered
    |--------------------------------------------------------------------------
    |
    | Sentral Cargo:
    |
    | 1 - 10 kg = first_price
    |
    | > 10 kg =
    | first_price
    | + ((weight - 10) × additional_price_per_kg)
    |
    */

    protected function calculateTiered(
        ShippingRate $rate,
        int $weight
    ): float {

        if ($rate->first_price === null) {
            throw new RuntimeException(
                "Tarif tiered untuk regency {$rate->regency_id} " .
                "tidak memiliki first_price."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 1 - 10 KG
        |--------------------------------------------------------------------------
        */

        if ($weight <= 10) {

            return round(
                (float) $rate->first_price,
                2
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Additional KG
        |--------------------------------------------------------------------------
        */

        if ($rate->additional_price_per_kg === null) {
            throw new RuntimeException(
                "Tarif tiered untuk regency {$rate->regency_id} " .
                "tidak memiliki additional_price_per_kg."
            );
        }

        $additionalWeight =
            $weight - 10;

        return round(
            (float) $rate->first_price
            +
            (
                $additionalWeight
                *
                (float) $rate->additional_price_per_kg
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Weight
    |--------------------------------------------------------------------------
    |
    | Berat courier selalu dibulatkan ke atas.
    |
    | 1.0  → 1
    | 1.1  → 2
    | 1.9  → 2
    | 10.0 → 10
    | 10.1 → 11
    |
    */

    public function normalizeWeight(
        int|float $weight
    ): int {

        if ($weight <= 0) {
            return 0;
        }

        return (int) ceil($weight);
    }

    /*
    |--------------------------------------------------------------------------
    | Estimate
    |--------------------------------------------------------------------------
    */

    public function estimate(
        int|float $weight,
        string $regencyId,
        ?string $courier = null,
        ?string $service = null,
    ): array {

        $normalizedWeight =
            $this->normalizeWeight($weight);

        if ($normalizedWeight <= 0) {
            throw new RuntimeException(
                'Berat pengiriman harus lebih besar dari 0 kg.'
            );
        }

        $service = self::DEFAULT_SERVICE;

        $fee = $this->calculate(
            weight: $weight,
            regencyId: $regencyId,
            courier: $courier,
            service: $service,
        );

        return [
            'weight' => $normalizedWeight,
            'fee' => $fee,
            'regency_id' => $regencyId,
            'courier' => $courier,
            'service' => $service,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Free Shipping
    |--------------------------------------------------------------------------
    */

    public function isFreeShipping(
        float $subtotal
    ): bool {

        return false;
    }
}