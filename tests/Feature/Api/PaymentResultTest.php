<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_result_returns_paid_for_settlement_payment(): void
    {
        $customer = Customer::create([
            'name' => 'Payment Result Customer',
            'email' => 'payment-result@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-payment-result-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-RESULT-001',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-RESULT-001',
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
                'address' => 'Jl. Payment Result Test No. 1',
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
            'transaction_id' => 'MIDTRANS-TRANSACTION-001',
            'snap_token' => 'SNAP-PAYMENT-RESULT-001',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->addHour(),
            'paid_at' => now(),
            'raw_response' => [
                'transaction_status' => 'settlement',
            ],
        ]);

        $response = $this->getJson(
            "/api/payment/result/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'order_number' => 'ORD-PAYMENT-RESULT-001',
                'tracking_token' => $trackingToken,
                'status' => 'paid',
                'payment_status' => 'paid',
                'transaction_status' => 'settlement',
                'payment_type' => 'bank_transfer',
                'redirect_tracking' => "/tracking/{$trackingToken}",
                'message' => 'Pembayaran berhasil.',
            ],
        ]);
    }

    public function test_payment_result_returns_pending_for_pending_payment(): void
    {
        $customer = Customer::create([
            'name' => 'Pending Payment Result Customer',
            'email' => 'pending-payment-result@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-pending-payment-result-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-RESULT-PENDING-001',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-RESULT-PENDING-001',
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
                'address' => 'Jl. Payment Result Pending No. 1',
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
            'snap_token' => 'SNAP-PAYMENT-RESULT-PENDING-001',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'pending',
            'fraud_status' => null,
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->addHour(),
            'paid_at' => null,
            'raw_response' => [
                'transaction_status' => 'pending',
            ],
        ]);

        $response = $this->getJson(
            "/api/payment/result/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'order_number' => 'ORD-PAYMENT-RESULT-PENDING-001',
                'tracking_token' => $trackingToken,
                'status' => 'pending',
                'payment_status' => 'pending',
                'transaction_status' => 'pending',
                'payment_type' => 'bank_transfer',
                'redirect_tracking' => "/tracking/{$trackingToken}",
                'message' => 'Menunggu pembayaran.',
            ],
        ]);
    }

    public function test_payment_result_returns_expired_for_expired_payment(): void
    {
        $customer = Customer::create([
            'name' => 'Expired Payment Result Customer',
            'email' => 'expired-payment-result@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-expired-payment-result-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-RESULT-EXPIRED-001',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-RESULT-EXPIRED-001',
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
                'address' => 'Jl. Payment Result Expired No. 1',
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
            'snap_token' => 'SNAP-PAYMENT-RESULT-EXPIRED-001',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'expire',
            'fraud_status' => null,
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->subMinute(),
            'paid_at' => null,
            'raw_response' => [
                'transaction_status' => 'expire',
            ],
        ]);

        $response = $this->getJson(
            "/api/payment/result/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'order_number' => 'ORD-PAYMENT-RESULT-EXPIRED-001',
                'tracking_token' => $trackingToken,
                'status' => 'expired',
                'payment_status' => 'pending',
                'transaction_status' => 'expire',
                'payment_type' => 'bank_transfer',
                'redirect_tracking' => "/tracking/{$trackingToken}",
                'message' => 'Pembayaran telah kedaluwarsa.',
            ],
        ]);
    }

    public function test_payment_result_returns_cancelled_for_cancelled_payment(): void
    {
        $customer = Customer::create([
            'name' => 'Cancelled Payment Result Customer',
            'email' => 'cancelled-payment-result@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-cancelled-payment-result-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-RESULT-CANCELLED-001',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-RESULT-CANCELLED-001',
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
                'address' => 'Jl. Payment Result Cancelled No. 1',
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

            'payment_expired_at' => now()->subMinute(),
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
            'snap_token' => 'SNAP-PAYMENT-RESULT-CANCELLED-001',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'cancel',
            'fraud_status' => null,
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->subMinute(),
            'paid_at' => null,
            'raw_response' => [
                'transaction_status' => 'cancel',
            ],
        ]);

        $response = $this->getJson(
            "/api/payment/result/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'order_number' => 'ORD-PAYMENT-RESULT-CANCELLED-001',
                'tracking_token' => $trackingToken,
                'status' => 'cancelled',
                'payment_status' => 'pending',
                'transaction_status' => 'cancel',
                'payment_type' => 'bank_transfer',
                'redirect_tracking' => "/tracking/{$trackingToken}",
                'message' => 'Pesanan telah dibatalkan.',
            ],
        ]);
    }

    public function test_payment_result_returns_failed_for_denied_payment(): void
    {
        $customer = Customer::create([
            'name' => 'Denied Payment Result Customer',
            'email' => 'denied-payment-result@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-denied-payment-result-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-RESULT-DENIED-001',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-RESULT-DENIED-001',
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
                'address' => 'Jl. Payment Result Denied No. 1',
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
            'transaction_id' => 'MIDTRANS-DENIED-001',
            'snap_token' => 'SNAP-PAYMENT-RESULT-DENIED-001',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'deny',
            'fraud_status' => 'deny',
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->addHour(),
            'paid_at' => null,
            'raw_response' => [
                'transaction_status' => 'deny',
            ],
        ]);

        $response = $this->getJson(
            "/api/payment/result/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'order_number' => 'ORD-PAYMENT-RESULT-DENIED-001',
                'tracking_token' => $trackingToken,
                'status' => 'failed',
                'payment_status' => 'pending',
                'transaction_status' => 'deny',
                'payment_type' => 'bank_transfer',
                'redirect_tracking' => "/tracking/{$trackingToken}",
                'message' => 'Pembayaran gagal.',
            ],
        ]);
    }

    public function test_payment_result_returns_refunded_for_refund_payment(): void
    {
        $customer = Customer::create([
            'name' => 'Refund Payment Result Customer',
            'email' => 'refund-payment-result@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-refund-payment-result-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-RESULT-REFUND-001',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-RESULT-REFUND-001',
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
                'address' => 'Jl. Payment Result Refund No. 1',
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

            'payment_expired_at' => now()->subHour(),
            'paid_at' => now()->subHour(),

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'MIDTRANS-REFUND-001',
            'snap_token' => 'SNAP-PAYMENT-RESULT-REFUND-001',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'refund',
            'fraud_status' => null,
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->subHour(),
            'paid_at' => now()->subHour(),
            'raw_response' => [
                'transaction_status' => 'refund',
            ],
        ]);

        $response = $this->getJson(
            "/api/payment/result/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'order_number' => 'ORD-PAYMENT-RESULT-REFUND-001',
                'tracking_token' => $trackingToken,
                'status' => 'refunded',
                'payment_status' => 'paid',
                'transaction_status' => 'refund',
                'payment_type' => 'bank_transfer',
                'redirect_tracking' => "/tracking/{$trackingToken}",
                'message' => 'Pembayaran telah dikembalikan.',
            ],
        ]);
    }

    public function test_payment_result_returns_refunded_for_partial_refund_payment(): void
    {
        $customer = Customer::create([
            'name' => 'Partial Refund Customer',
            'email' => 'partial-refund@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-partial-refund-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-RESULT-PARTIAL-REFUND-001',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-RESULT-PARTIAL-REFUND-001',
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
                'address' => 'Jl. Payment Result Partial Refund No. 1',
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

            'payment_expired_at' => now()->subHour(),
            'paid_at' => now()->subHour(),

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'MIDTRANS-PARTIAL-REFUND-001',
            'snap_token' => 'SNAP-PAYMENT-RESULT-PARTIAL-REFUND-001',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'partial_refund',
            'fraud_status' => null,
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->subHour(),
            'paid_at' => now()->subHour(),
            'raw_response' => [
                'transaction_status' => 'partial_refund',
            ],
        ]);

        $response = $this->getJson(
            "/api/payment/result/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'order_number' => 'ORD-PAYMENT-RESULT-PARTIAL-REFUND-001',
                'tracking_token' => $trackingToken,
                'status' => 'refunded',
                'payment_status' => 'paid',
                'transaction_status' => 'partial_refund',
                'payment_type' => 'bank_transfer',
                'redirect_tracking' => "/tracking/{$trackingToken}",
                'message' => 'Pembayaran telah dikembalikan.',
            ],
        ]);
    }

    public function test_payment_result_returns_refunded_for_refunded_payment(): void
    {
        $customer = Customer::create([
            'name' => 'Refunded Payment Result Customer',
            'email' => 'refunded-payment-result@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-refunded-payment-result-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-RESULT-REFUNDED-001',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-RESULT-REFUNDED-001',
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
                'address' => 'Jl. Payment Result Refunded No. 1',
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

            'payment_expired_at' => now()->subHour(),
            'paid_at' => now()->subHour(),

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'MIDTRANS-REFUNDED-001',
            'snap_token' => 'SNAP-PAYMENT-RESULT-REFUNDED-001',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'refunded',
            'fraud_status' => null,
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->subHour(),
            'paid_at' => now()->subHour(),
            'raw_response' => [
                'transaction_status' => 'refunded',
            ],
        ]);

        $response = $this->getJson(
            "/api/payment/result/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'order_number' => 'ORD-PAYMENT-RESULT-REFUNDED-001',
                'tracking_token' => $trackingToken,
                'status' => 'refunded',
                'payment_status' => 'paid',
                'transaction_status' => 'refunded',
                'payment_type' => 'bank_transfer',
                'redirect_tracking' => "/tracking/{$trackingToken}",
                'message' => 'Pembayaran telah dikembalikan.',
            ],
        ]);
    }

    public function test_payment_result_falls_back_to_paid_when_payment_status_is_paid(): void
    {
        $customer = Customer::create([
            'name' => 'Fallback Paid Customer',
            'email' => 'fallback-paid@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-fallback-paid-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-RESULT-FALLBACK-PAID-001',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-RESULT-FALLBACK-PAID-001',
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
                'address' => 'Jl. Payment Result Fallback Paid No. 1',
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

            'payment_expired_at' => now()->subHour(),
            'paid_at' => now()->subHour(),

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'MIDTRANS-FALLBACK-PAID-001',
            'snap_token' => 'SNAP-PAYMENT-RESULT-FALLBACK-PAID-001',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'unknown_status',
            'fraud_status' => null,
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->subHour(),
            'paid_at' => now()->subHour(),
            'raw_response' => [
                'transaction_status' => 'unknown_status',
            ],
        ]);

        $response = $this->getJson(
            "/api/payment/result/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => 'paid',
                'payment_status' => 'paid',
                'transaction_status' => 'unknown_status',
                'message' => 'Pembayaran berhasil.',
            ],
        ]);
    }

    public function test_payment_result_falls_back_to_pending_when_payment_status_is_not_paid(): void
    {
        $customer = Customer::create([
            'name' => 'Fallback Pending Customer',
            'email' => 'fallback-pending@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-fallback-pending-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-RESULT-FALLBACK-PENDING-001',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-RESULT-FALLBACK-PENDING-001',
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
                'address' => 'Jl. Payment Result Fallback Pending No. 1',
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
            'snap_token' => 'SNAP-PAYMENT-RESULT-FALLBACK-PENDING-001',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'unknown_status',
            'fraud_status' => null,
            'gross_amount' => 1_025_000,
            'bank' => 'bca',
            'va_number' => '1234567890',
            'expired_at' => now()->addHour(),
            'paid_at' => null,
            'raw_response' => [
                'transaction_status' => 'unknown_status',
            ],
        ]);

        $response = $this->getJson(
            "/api/payment/result/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => 'pending',
                'payment_status' => 'pending',
                'transaction_status' => 'unknown_status',
                'message' => 'Menunggu pembayaran.',
            ],
        ]);
    }

    public function test_payment_result_returns_result_when_payment_data_is_missing(): void
    {
        $customer = Customer::create([
            'name' => 'No Payment Customer',
            'email' => 'no-payment@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-no-payment-test',
        ]);

        $trackingToken = (string) Str::ulid();

        Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-RESULT-NO-PAYMENT-001',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-RESULT-NO-PAYMENT-001',
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
                'address' => 'Jl. Payment Result No Payment No. 1',
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
            "/api/payment/result/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJson([
            'success' => true,
            'data' => [
                'order_number' => 'ORD-PAYMENT-RESULT-NO-PAYMENT-001',
                'tracking_token' => $trackingToken,
                'status' => 'pending',
                'payment_status' => 'pending',
                'transaction_status' => null,
                'payment_type' => null,
                'redirect_tracking' => "/tracking/{$trackingToken}",
                'message' => 'Menunggu pembayaran.',
            ],
        ]);
    }

    public function test_payment_result_returns_not_found_for_invalid_tracking_token(): void
    {
        $response = $this->getJson(
            '/api/payment/result/INVALID-TRACKING-TOKEN-999'
        );

        $response->assertNotFound();

        $response->assertJson([
            'success' => false,
            'message' => 'Resource not found.',
            'errors' => null,
        ]);
    }
}