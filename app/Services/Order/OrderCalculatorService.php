<?php

namespace App\Services\Order;

use App\Models\Voucher;
use Illuminate\Support\Collection;
use RuntimeException;
use App\Services\Shipping\ShippingService;
use App\Services\Voucher\VoucherService;

class OrderCalculatorService
{
    private const DEFAULT_SERVICE = 'regular';

    public function __construct(
        protected VoucherService $voucherService,
        protected ShippingService $shippingService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Calculate Checkout
    |--------------------------------------------------------------------------
    */

    public function calculate(
        Collection $products,
        ?Voucher $voucher,
        string $courier,
        string $service,
        string $regencyId,
    ): array {

        $subtotal = 0;
        $totalWeight = 0;

        foreach ($products as $item) {

            $product = $item->product;

            /*
            |--------------------------------------------------------------------------
            | Specification
            |--------------------------------------------------------------------------
            */

            $specification = $product->specification;

            if (!$specification) {
                throw new RuntimeException(
                    "Produk {$product->name} belum memiliki spesifikasi."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Product Price
            |--------------------------------------------------------------------------
            */

            $price =
                $product->is_sale &&
                $product->discount_price
                    ? $product->discount_price
                    : $product->original_price;

            $subtotal +=
                $price * $item->quantity;

            /*
            |--------------------------------------------------------------------------
            | Product Weight
            |--------------------------------------------------------------------------
            */

            $totalWeight +=
                $specification->packing_weight *
                $item->quantity;
        }

        /*
        |--------------------------------------------------------------------------
        | Voucher
        |--------------------------------------------------------------------------
        */

        $this->voucherService->validate(
            $voucher,
            $subtotal
        );

        $voucherDiscount =
            $this->voucherService->calculateDiscount(
                $voucher,
                $subtotal
            );

        /*
        |--------------------------------------------------------------------------
        | Shipping
        |--------------------------------------------------------------------------
        |
        | Service selalu regular.
        |
        */

        $service = self::DEFAULT_SERVICE;

        /*
        |--------------------------------------------------------------------------
        | Normalize Shipping Weight
        |--------------------------------------------------------------------------
        |
        | ShippingService akan membulatkan berat ke atas
        | sebelum menghitung tarif.
        |
        | Contoh:
        | 10.2 kg -> 11 kg
        |
        */

        $shippingWeight = (int) ceil($totalWeight);

        /*
        |--------------------------------------------------------------------------
        | Shipping Fee
        |--------------------------------------------------------------------------
        */

        $shippingFee = $this->shippingService->calculate(
            weight: $totalWeight,
            regencyId: $regencyId,
            courier: $courier,
            service: $service,
        );

        /*
        |--------------------------------------------------------------------------
        | Grand Total
        |--------------------------------------------------------------------------
        */

        return [
            'subtotal' => $subtotal,

            /*
            * Berat asli produk.
            */
            'total_weight' => $totalWeight,

            /*
            * Berat setelah pembulatan untuk tarif.
            */
            'shipping_weight' => $shippingWeight,

            'voucher_discount' => $voucherDiscount,

            'shipping_fee' => $shippingFee,

            'total_payment' =>
                $subtotal
                - $voucherDiscount
                + $shippingFee,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Shipping Only
    |--------------------------------------------------------------------------
    */

    public function calculateShipping(
        Collection $products,
        string $regencyId,
        string $courier,
        ?string $service = null,
    ): array {

        $totalWeight = 0;

        foreach ($products as $item) {

            $product = $item->product;

            $specification = $product->specification;

            if (!$specification) {
                throw new RuntimeException(
                    "Produk {$product->name} belum memiliki spesifikasi."
                );
            }

            $totalWeight +=
                $specification->packing_weight *
                $item->quantity;
        }

        /*
        |--------------------------------------------------------------------------
        | Service
        |--------------------------------------------------------------------------
        |
        | Nilai dari frontend diabaikan.
        | Sistem selalu menggunakan regular.
        |
        */

        $service = self::DEFAULT_SERVICE;

        $shipping = $this->shippingService->estimate(
            weight: $totalWeight,
            regencyId: $regencyId,
            courier: $courier,
            service: $service,
        );

        return [
            'weight' => $shipping['weight'],
            'fee' => $shipping['fee'],
            'regency_id' => $regencyId,
            'courier' => $courier,
            'service' => $service,
        ];
    }
}