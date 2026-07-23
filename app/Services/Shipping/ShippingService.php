<?php

namespace App\Services\Shipping;

class ShippingService
{
    /*
    |--------------------------------------------------------------------------
    | Default Shipping Fee
    |--------------------------------------------------------------------------
    */

    protected int $defaultFee = 25000;

    /*
    |--------------------------------------------------------------------------
    | Calculate Shipping
    |--------------------------------------------------------------------------
    */

    public function calculate(
        int $weight,
        ?string $courier = null,
        ?string $service = null
    ): float {

        /**
         * TODO:
         * Integrasi RajaOngkir / Biteship
         */

        return $this->defaultFee;

    }

    /*
    |--------------------------------------------------------------------------
    | Estimate Shipping
    |--------------------------------------------------------------------------
    */

    public function estimate(
        int $weight,
        ?string $courier = null
    ): array {

        return [

            'fee' => $this->calculate(
                $weight,
                $courier
            ),

            'etd' => '2-4 Hari',

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