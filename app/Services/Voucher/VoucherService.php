<?php

namespace App\Services\Voucher;

use App\Models\Voucher;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    /*
    |--------------------------------------------------------------------------
    | Validate Voucher
    |--------------------------------------------------------------------------
    */

    public function validate(
        ?Voucher $voucher,
        float $subtotal
    ): void {

        if (!$voucher) {
            return;
        }

        if (!$voucher->is_active) {

            throw ValidationException::withMessages([

                'voucher' => 'Voucher tidak aktif.'

            ]);

        }

        if ($voucher->expired_at &&
            now()->greaterThan($voucher->expired_at)
        ) {

            throw ValidationException::withMessages([

                'voucher' => 'Voucher sudah expired.'

            ]);

        }

        if (
            $voucher->minimum_purchase &&
            $subtotal < $voucher->minimum_purchase
        ) {

            throw ValidationException::withMessages([

                'voucher' =>
                    'Minimal pembelian belum memenuhi.'

            ]);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Discount
    |--------------------------------------------------------------------------
    */

    public function calculateDiscount(
        ?Voucher $voucher,
        float $subtotal
    ): float {

        if (!$voucher) {

            return 0;

        }

        if (
            $voucher->discount_type === 'fixed'
        ) {

            return min(

                $voucher->discount_value,

                $subtotal

            );

        }

        $discount =

            ($subtotal * $voucher->discount_value)

            / 100;

        if ($voucher->maximum_discount) {

            $discount = min(

                $discount,

                $voucher->maximum_discount

            );

        }

        return $discount;

    }

    /*
    |--------------------------------------------------------------------------
    | Is Expired
    |--------------------------------------------------------------------------
    */

    public function isExpired(
        Voucher $voucher
    ): bool {

        if (!$voucher->expired_at) {

            return false;

        }

        return now()->greaterThan(

            $voucher->expired_at

        );

    }

    /*
    |--------------------------------------------------------------------------
    | Is Valid
    |--------------------------------------------------------------------------
    */

    public function isValid(
        ?Voucher $voucher,
        float $subtotal
    ): bool {

        try {

            $this->validate(

                $voucher,

                $subtotal

            );

            return true;

        }

        catch (\Throwable) {

            return false;

        }

    }

}