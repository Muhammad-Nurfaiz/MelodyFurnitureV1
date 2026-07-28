<?php

namespace App\Services\Order;

use App\Models\Voucher;
use Illuminate\Support\Collection;
use RuntimeException;
use App\Services\Shipping\ShippingService;
use App\Services\Voucher\VoucherService;

class OrderCalculatorService
{
    public function __construct(
        protected VoucherService $voucherService,
        protected ShippingService $shippingService,
    ) {}

    /**
     * Hitung seluruh biaya checkout.
     */
    public function calculate(Collection $products,?Voucher $voucher,string $courier,string $service,): array {
        $subtotal = 0;
        $totalWeight = 0;
        foreach ($products as $item) {
            $product = $item['product'];

            /*
            |--------------------------------------------------------------------------
            | Pastikan Specification sudah diload
            |--------------------------------------------------------------------------
            */

            $specification = $product->specification;

            if (!$specification) {
                throw new RuntimeException("Produk {$product->name} belum memiliki spesifikasi.");
            }

            /*
            |--------------------------------------------------------------------------
            | Subtotal
            |--------------------------------------------------------------------------
            */

            $price = $product->is_sale && $product->discount_price ? $product->discount_price : $product->original_price;
            $subtotal += $price * $item['quantity'];

            /*
            |--------------------------------------------------------------------------
            | Berat Pengiriman (Packing Weight)
            |--------------------------------------------------------------------------
            */

            $totalWeight += $specification->packing_weight * $item['quantity'];
        }

        /*
        |--------------------------------------------------------------------------
        | Voucher
        |--------------------------------------------------------------------------
        */

        $this->voucherService->validate($voucher,$subtotal);

        $voucherDiscount = $this->voucherService->calculateDiscount($voucher,$subtotal);

        /*
        |--------------------------------------------------------------------------
        | Shipping
        |--------------------------------------------------------------------------
        */

        $shippingFee = $this->shippingService->calculate($totalWeight,$courier,$service);

        /*
        |--------------------------------------------------------------------------
        | Grand Total
        |--------------------------------------------------------------------------
        */

        return [
            'subtotal' => $subtotal,
            'total_weight' => $totalWeight,
            'voucher_discount' => $voucherDiscount,
            'shipping_fee' => $shippingFee,
            'total_payment' => $subtotal - $voucherDiscount + $shippingFee,
        ];
    }
}