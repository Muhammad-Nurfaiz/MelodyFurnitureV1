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

        if (! $voucher) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Active
        |--------------------------------------------------------------------------
        */

        if (! $voucher->is_active) {
            throw ValidationException::withMessages([
                'voucher' => 'Voucher tidak aktif.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Start Date
        |--------------------------------------------------------------------------
        */

        if (
            $voucher->start_date &&
            now()->lessThan($voucher->start_date)
        ) {
            throw ValidationException::withMessages([
                'voucher' => 'Voucher belum mulai berlaku.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Expiry Date
        |--------------------------------------------------------------------------
        */

        if (
            $voucher->expiry_date &&
            now()->greaterThan($voucher->expiry_date)
        ) {
            throw ValidationException::withMessages([
                'voucher' => 'Voucher sudah expired.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Minimum Order Amount
        |--------------------------------------------------------------------------
        */

        if (
            $voucher->min_order_amount > 0 &&
            $subtotal < $voucher->min_order_amount
        ) {
            throw ValidationException::withMessages([
                'voucher' => 'Minimal pembelian belum memenuhi.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Usage Limit
        |--------------------------------------------------------------------------
        */

        if (
            $voucher->usage_limit !== null &&
            $voucher->used_count >= $voucher->usage_limit
        ) {
            throw ValidationException::withMessages([
                'voucher' => 'Batas penggunaan voucher sudah tercapai.',
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

        if (! $voucher) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Fixed
        |--------------------------------------------------------------------------
        */

        if ($voucher->discount_type === 'fixed') {

            return min(
                (float) $voucher->discount_value,
                $subtotal
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Percentage
        |--------------------------------------------------------------------------
        */

        $discount = (
            $subtotal * (float) $voucher->discount_value
        ) / 100;

        /*
        |--------------------------------------------------------------------------
        | Maximum Discount
        |--------------------------------------------------------------------------
        */

        if (
            $voucher->max_discount_amount !== null &&
            $voucher->max_discount_amount > 0
        ) {
            $discount = min(
                $discount,
                (float) $voucher->max_discount_amount
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Never Exceed Subtotal
        |--------------------------------------------------------------------------
        */

        return min($discount, $subtotal);
    }


    /*
    |--------------------------------------------------------------------------
    | Is Expired
    |--------------------------------------------------------------------------
    */

    public function isExpired(
        Voucher $voucher
    ): bool {

        if (! $voucher->expiry_date) {
            return false;
        }

        return now()->greaterThan(
            $voucher->expiry_date
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Is Started
    |--------------------------------------------------------------------------
    */

    public function isStarted(
        Voucher $voucher
    ): bool {

        if (! $voucher->start_date) {
            return true;
        }

        return now()->greaterThanOrEqualTo(
            $voucher->start_date
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Usage Limit Reached
    |--------------------------------------------------------------------------
    */

    public function isUsageLimitReached(
        Voucher $voucher
    ): bool {

        if ($voucher->usage_limit === null) {
            return false;
        }

        return $voucher->used_count >= $voucher->usage_limit;
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

        } catch (\Throwable) {

            return false;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Find By Code
    |--------------------------------------------------------------------------
    */

    public function findByCode(
        string $code
    ): ?Voucher {

        return Voucher::query()
            ->where('code', $code)
            ->first();
    }

    public function markUsed(Voucher $voucher): void
    {
        $voucher->increment('used_count');
    }

    
}
