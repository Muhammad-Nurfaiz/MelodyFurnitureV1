<?php

namespace App\Services\Order;

use App\Models\Voucher;
use Illuminate\Support\Collection;
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
    public function calculate(
        Collection $products,
        ?Voucher $voucher,
        string $courier,
        string $service,
    ): array {

        $subtotal = 0;

        $weight = 0;

        foreach ($products as $item) {

            $subtotal +=
                $item['product']->price
                *
                $item['qty'];

            $weight +=
                $item['product']->weight
                *
                $item['qty'];

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
            $this->voucherService
                ->calculateDiscount(
                    $voucher,
                    $subtotal
                );

        /*
        |--------------------------------------------------------------------------
        | Shipping
        |--------------------------------------------------------------------------
        */

        $shippingFee =
            $this->shippingService
                ->calculate(
                    $weight,
                    $courier,
                    $service
                );

        /*
        |--------------------------------------------------------------------------
        | Grand Total
        |--------------------------------------------------------------------------
        */

        return [

            'subtotal' => $subtotal,

            'weight' => $weight,

            'voucher_discount' => $voucherDiscount,

            'shipping_fee' => $shippingFee,

            'total_payment' =>
                $subtotal
                -
                $voucherDiscount
                +
                $shippingFee,

        ];

    }
}