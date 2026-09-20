<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResumePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_resume_pending_payment(): void
    {
        $customer = Customer::create([
            'name' => 'Resume Payment Customer',
            'email' => 'resume-payment@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-resume-payment-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-RESUME-001',
            'midtrans_order_id' => 'MIDTRANS-RESUME-001',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 25_000,
            'shipping_fee' => 25_000,
            'total_payment' => 1_025_000,

            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'address' => 'Jl. Resume Test No. 1',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65145',
            ],

            'shipping_method' => 'regular',
            'courier' => 'jnt_cargo',
            'tracking_number' => null,
            'total_weight' => 1,

            'status' => 'pending',
            'payment_status' => 'pending',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => null,

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'transaction_id' => null,
            'snap_token' => 'SNAP-TEST-TOKEN',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'pending',
            'fraud_status' => null,
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->addHour(),
            'paid_at' => null,
            'raw_response' => [
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/SNAP-TEST-TOKEN',
            ],
            'raw_notification' => null,
        ]);

        $response = $this->getJson(
            "/api/payments/resume/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'can_pay' => true,
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/SNAP-TEST-TOKEN',
                'snap_token' => 'SNAP-TEST-TOKEN',
                'payment_status' => 'pending',
            ],
        ]);

        $response->assertJsonPath(
            'data.expired_at',
            fn ($value) => $value !== null
        );
    }

    public function test_customer_cannot_resume_payment_when_order_is_already_paid(): void
    {
        $customer = Customer::create([
            'name' => 'Paid Resume Customer',
            'email' => 'paid-resume@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-paid-resume-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-RESUME-PAID-001',
            'midtrans_order_id' => 'MIDTRANS-RESUME-PAID-001',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 25_000,
            'shipping_fee' => 25_000,
            'total_payment' => 1_025_000,

            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'address' => 'Jl. Resume Paid Test No. 1',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65145',
            ],

            'shipping_method' => 'regular',
            'courier' => 'jnt_cargo',
            'tracking_number' => null,
            'total_weight' => 1,

            'status' => 'processing',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'TRANSACTION-PAID-001',
            'snap_token' => 'SNAP-PAID-TOKEN',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->subHour(),
            'paid_at' => now(),
            'raw_response' => [
                'redirect_url' => 'https://example.com/payment',
            ],
            'raw_notification' => null,
        ]);

        $response = $this->getJson(
            "/api/payments/resume/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'can_pay' => false,
                'message' => 'Pembayaran sudah selesai.',
            ],
        ]);
    }

    public function test_customer_cannot_resume_payment_when_order_is_cancelled(): void
    {
        $customer = Customer::create([
            'name' => 'Cancelled Resume Customer',
            'email' => 'cancelled-resume@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-cancelled-resume-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-RESUME-CANCELLED-001',
            'midtrans_order_id' => 'MIDTRANS-RESUME-CANCELLED-001',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 25_000,
            'shipping_fee' => 25_000,
            'total_payment' => 1_025_000,

            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'address' => 'Jl. Resume Cancelled Test No. 1',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65145',
            ],

            'shipping_method' => 'regular',
            'courier' => 'jnt_cargo',
            'tracking_number' => null,
            'total_weight' => 1,

            'status' => 'cancelled',
            'payment_status' => 'pending',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => null,

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => now(),
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => null,
            'snap_token' => 'SNAP-CANCELLED-TOKEN',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'pending',
            'fraud_status' => null,
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->addHour(),
            'paid_at' => null,
            'raw_response' => [
                'redirect_url' => 'https://example.com/payment',
            ],
            'raw_notification' => null,
        ]);

        $response = $this->getJson(
            "/api/payments/resume/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'can_pay' => false,
                'message' => 'Pesanan sudah dibatalkan.',
            ],
        ]);
    }

    public function test_customer_cannot_resume_expired_payment(): void
    {
        $customer = Customer::create([
            'name' => 'Expired Resume Customer',
            'email' => 'expired-resume@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-expired-resume-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-RESUME-EXPIRED-001',
            'midtrans_order_id' => 'MIDTRANS-RESUME-EXPIRED-001',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 25_000,
            'shipping_fee' => 25_000,
            'total_payment' => 1_025_000,

            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'address' => 'Jl. Resume Expired Test No. 1',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65145',
            ],

            'shipping_method' => 'regular',
            'courier' => 'jnt_cargo',
            'tracking_number' => null,
            'total_weight' => 1,

            'status' => 'pending',
            'payment_status' => 'pending',

            'payment_expired_at' => now()->subMinute(),
            'paid_at' => null,

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => null,
            'snap_token' => 'SNAP-EXPIRED-TOKEN',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'pending',
            'fraud_status' => null,
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->subMinute(),
            'paid_at' => null,
            'raw_response' => [
                'redirect_url' => 'https://example.com/payment',
            ],
            'raw_notification' => null,
        ]);

        $response = $this->getJson(
            "/api/payments/resume/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'can_pay' => false,
                'message' => 'Pembayaran sudah kedaluwarsa.',
            ],
        ]);
    }

    public function test_resume_payment_returns_error_when_payment_data_is_missing(): void
    {
        $customer = Customer::create([
            'name' => 'Missing Payment Customer',
            'email' => 'missing-payment@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-missing-payment-test',
        ]);

        $trackingToken = (string) Str::ulid();

        Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-RESUME-MISSING-PAYMENT-001',
            'midtrans_order_id' => 'MIDTRANS-RESUME-MISSING-PAYMENT-001',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 25_000,
            'shipping_fee' => 25_000,
            'total_payment' => 1_025_000,

            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'address' => 'Jl. Missing Payment Test No. 1',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65145',
            ],

            'shipping_method' => 'regular',
            'courier' => 'jnt_cargo',
            'tracking_number' => null,
            'total_weight' => 1,

            'status' => 'pending',
            'payment_status' => 'pending',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => null,

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        $response = $this->getJson(
            "/api/payments/resume/{$trackingToken}"
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Data pembayaran tidak ditemukan.',
            'errors' => null,
        ]);
    }

    public function test_resume_payment_returns_error_when_payment_link_is_missing(): void
    {
        $customer = Customer::create([
            'name' => 'Missing Link Customer',
            'email' => 'missing-link@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-missing-link-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-RESUME-MISSING-LINK-001',
            'midtrans_order_id' => 'MIDTRANS-RESUME-MISSING-LINK-001',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 25_000,
            'shipping_fee' => 25_000,
            'total_payment' => 1_025_000,

            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'address' => 'Jl. Missing Link Test No. 1',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65145',
            ],

            'shipping_method' => 'regular',
            'courier' => 'jnt_cargo',
            'tracking_number' => null,
            'total_weight' => 1,

            'status' => 'pending',
            'payment_status' => 'pending',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => null,

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => null,
            'snap_token' => null,
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'pending',
            'fraud_status' => null,
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->addHour(),
            'paid_at' => null,
            'raw_response' => [
                'redirect_url' => null,
            ],
            'raw_notification' => null,
        ]);

        $response = $this->getJson(
            "/api/payments/resume/{$trackingToken}"
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Link pembayaran tidak ditemukan.',
            'errors' => null,
        ]);
    }

    public function test_resume_payment_returns_not_found_for_invalid_tracking_token(): void
    {
        $invalidTrackingToken = (string) Str::ulid();

        $response = $this->getJson(
            "/api/payments/resume/{$invalidTrackingToken}"
        );

        $response->assertStatus(404);

        $response->assertJson([
            'success' => false,
            'message' => 'Resource not found.',
            'errors' => null,
        ]);
    }
}