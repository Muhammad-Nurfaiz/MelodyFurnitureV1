<?php

namespace Tests\Unit\Services\Voucher;

use App\Models\Voucher;
use App\Services\Voucher\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VoucherServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VoucherService $voucherService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->voucherService = app(
            VoucherService::class
        );

        Carbon::setTestNow(
            Carbon::parse('2026-08-08 12:00:00')
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function createVoucher(
        array $overrides = []
    ): Voucher {
        return Voucher::create(array_merge([
            'code' => 'TEST10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => null,
            'expiry_date' => now()->addDays(30),
            'usage_limit' => 100,
            'used_count' => 0,
            'is_active' => true,
        ], $overrides));
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE
    |--------------------------------------------------------------------------
    */

    public function test_active_voucher_is_valid(): void
    {
        $voucher = $this->createVoucher();

        $this->voucherService->validate(
            $voucher,
            1_000_000
        );

        $this->assertTrue(true);
    }

    public function test_inactive_voucher_is_rejected(): void
    {
        $voucher = $this->createVoucher([
            'is_active' => false,
        ]);

        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'Voucher tidak aktif.'
        );

        $this->voucherService->validate(
            $voucher,
            1_000_000
        );
    }

    public function test_voucher_before_start_date_is_rejected(): void
    {
        $voucher = $this->createVoucher([
            'start_date' => now()->addDay(),
        ]);

        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'Voucher belum mulai berlaku.'
        );

        $this->voucherService->validate(
            $voucher,
            1_000_000
        );
    }

    public function test_expired_voucher_is_rejected(): void
    {
        $voucher = $this->createVoucher([
            'expiry_date' => now()->subMinute(),
        ]);

        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'Voucher sudah expired.'
        );

        $this->voucherService->validate(
            $voucher,
            1_000_000
        );
    }

    public function test_minimum_purchase_is_enforced(): void
    {
        $voucher = $this->createVoucher([
            'min_order_amount' => 1_000_000,
        ]);

        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'Minimal pembelian belum memenuhi.'
        );

        $this->voucherService->validate(
            $voucher,
            500_000
        );
    }

    public function test_minimum_purchase_is_passed_when_subtotal_is_sufficient(): void
    {
        $voucher = $this->createVoucher([
            'min_order_amount' => 1_000_000,
        ]);

        $this->voucherService->validate(
            $voucher,
            1_000_000
        );

        $this->assertTrue(true);
    }

    public function test_usage_limit_is_enforced(): void
    {
        $voucher = $this->createVoucher([
            'usage_limit' => 100,
            'used_count' => 100,
        ]);

        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'Batas penggunaan voucher sudah tercapai.'
        );

        $this->voucherService->validate(
            $voucher,
            1_000_000
        );
    }

    public function test_unlimited_voucher_is_valid(): void
    {
        $voucher = $this->createVoucher([
            'usage_limit' => null,
            'used_count' => 999999,
        ]);

        $this->voucherService->validate(
            $voucher,
            1_000_000
        );

        $this->assertTrue(true);
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATE DISCOUNT
    |--------------------------------------------------------------------------
    */

    public function test_percentage_discount_is_calculated_correctly(): void
    {
        $voucher = $this->createVoucher([
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ]);

        $discount = $this->voucherService
            ->calculateDiscount(
                $voucher,
                2_000_000
            );

        $this->assertSame(
            200_000.0,
            $discount
        );
    }

    public function test_fixed_discount_is_calculated_correctly(): void
    {
        $voucher = $this->createVoucher([
            'discount_type' => 'fixed',
            'discount_value' => 150_000,
        ]);

        $discount = $this->voucherService
            ->calculateDiscount(
                $voucher,
                2_000_000
            );

        $this->assertSame(
            150_000.0,
            $discount
        );
    }

    public function test_fixed_discount_cannot_exceed_subtotal(): void
    {
        $voucher = $this->createVoucher([
            'discount_type' => 'fixed',
            'discount_value' => 3_000_000,
        ]);

        $discount = $this->voucherService
            ->calculateDiscount(
                $voucher,
                2_000_000
            );

        $this->assertSame(
            2_000_000.0,
            $discount
        );
    }

    public function test_percentage_discount_respects_maximum_discount(): void
    {
        $voucher = $this->createVoucher([
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'max_discount_amount' => 100_000,
        ]);

        $discount = $this->voucherService
            ->calculateDiscount(
                $voucher,
                1_000_000
            );

        $this->assertSame(
            100_000.0,
            $discount
        );
    }

    public function test_percentage_discount_without_maximum_discount_is_not_capped(): void
    {
        $voucher = $this->createVoucher([
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'max_discount_amount' => null,
        ]);

        $discount = $this->voucherService
            ->calculateDiscount(
                $voucher,
                1_000_000
            );

        $this->assertSame(
            200_000.0,
            $discount
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function test_is_expired_returns_true_for_expired_voucher(): void
    {
        $voucher = $this->createVoucher([
            'expiry_date' => now()->subMinute(),
        ]);

        $this->assertTrue(
            $this->voucherService->isExpired($voucher)
        );
    }

    public function test_is_expired_returns_false_for_active_voucher(): void
    {
        $voucher = $this->createVoucher();

        $this->assertFalse(
            $this->voucherService->isExpired($voucher)
        );
    }

    public function test_is_started_returns_true_when_start_date_is_null(): void
    {
        $voucher = $this->createVoucher([
            'start_date' => null,
        ]);

        $this->assertTrue(
            $this->voucherService->isStarted($voucher)
        );
    }

    public function test_is_started_returns_false_before_start_date(): void
    {
        $voucher = $this->createVoucher([
            'start_date' => now()->addDay(),
        ]);

        $this->assertFalse(
            $this->voucherService->isStarted($voucher)
        );
    }

    public function test_usage_limit_reached_returns_true(): void
    {
        $voucher = $this->createVoucher([
            'usage_limit' => 10,
            'used_count' => 10,
        ]);

        $this->assertTrue(
            $this->voucherService
                ->isUsageLimitReached($voucher)
        );
    }

    public function test_usage_limit_reached_returns_false_for_unlimited_voucher(): void
    {
        $voucher = $this->createVoucher([
            'usage_limit' => null,
            'used_count' => 999,
        ]);

        $this->assertFalse(
            $this->voucherService
                ->isUsageLimitReached($voucher)
        );
    }

    public function test_is_valid_returns_true_for_valid_voucher(): void
    {
        $voucher = $this->createVoucher();

        $this->assertTrue(
            $this->voucherService->isValid(
                $voucher,
                1_000_000
            )
        );
    }

    public function test_is_valid_returns_false_for_invalid_voucher(): void
    {
        $voucher = $this->createVoucher([
            'is_active' => false,
        ]);

        $this->assertFalse(
            $this->voucherService->isValid(
                $voucher,
                1_000_000
            )
        );
    }

    public function test_null_voucher_is_valid(): void
    {
        $this->voucherService->validate(
            null,
            1_000_000
        );

        $this->assertTrue(
            $this->voucherService->isValid(
                null,
                1_000_000
            )
        );
    }

    public function test_null_voucher_discount_is_zero(): void
    {
        $this->assertSame(
            0.0,
            $this->voucherService
                ->calculateDiscount(
                    null,
                    1_000_000
                )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MARK USED
    |--------------------------------------------------------------------------
    */

    public function test_mark_used_increments_used_count(): void
    {
        $voucher = $this->createVoucher([
            'used_count' => 0,
        ]);

        $this->voucherService->markUsed(
            $voucher
        );

        $this->assertSame(
            1,
            $voucher->fresh()->used_count
        );
    }
}