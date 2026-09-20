<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_download_own_paid_order_invoice(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $customer = Customer::create([
            'name' => 'Invoice Test Customer',
            'email' => 'invoice@test.local',
            'phone' => '081234567891',
            'guest_token' => 'guest-token-invoice-test',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */

        $order = Order::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-INVOICE-PAID',
            'midtrans_order_id' => 'MIDTRANS-INVOICE-PAID',
            'tracking_token' => (string) \Illuminate\Support\Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 1_000_000,

            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'address' => 'Alamat Invoice Test',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
            ],

            'shipping_method' => 'REG',
            'courier' => 'jnt_cargo',
            'total_weight' => 1,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHours(1),
            'paid_at' => now(),

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Request
        |--------------------------------------------------------------------------
        */

        $response = $this->withHeader(
            'X-Guest-Session-Id',
            $customer->guest_token
        )->get(
            "/api/orders/{$order->id}/invoice"
        );

        /*
        |--------------------------------------------------------------------------
        | Assertions
        |--------------------------------------------------------------------------
        */

        $response->assertOk();

        $response->assertHeader(
            'Content-Type',
            'application/pdf'
        );

        $this->assertNotEmpty(
            $response->getContent()
        );
    }

    public function test_customer_cannot_download_invoice_belonging_to_another_customer(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Customer A — Session yang digunakan
        |--------------------------------------------------------------------------
        */

        $customerA = Customer::create([
            'name' => 'Customer A',
            'email' => 'customer-a@test.local',
            'phone' => '081234567891',
            'guest_token' => 'guest-token-customer-a',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Customer B — Pemilik Order
        |--------------------------------------------------------------------------
        */

        $customerB = Customer::create([
            'name' => 'Customer B',
            'email' => 'customer-b@test.local',
            'phone' => '081234567892',
            'guest_token' => 'guest-token-customer-b',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Order milik Customer B
        |--------------------------------------------------------------------------
        */

        $order = Order::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'customer_id' => $customerB->id,

            'order_number' => 'ORD-INVOICE-OTHER',
            'midtrans_order_id' => 'MIDTRANS-INVOICE-OTHER',
            'tracking_token' => (string) \Illuminate\Support\Str::ulid(),

            'customer_name' => $customerB->name,
            'customer_phone' => $customerB->phone,
            'customer_email' => $customerB->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 1_000_000,

            'shipping_address' => [
                'recipient_name' => $customerB->name,
                'phone' => $customerB->phone,
                'address' => 'Alamat Customer B',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
            ],

            'shipping_method' => 'REG',
            'courier' => 'jnt_cargo',
            'total_weight' => 1,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHours(1),
            'paid_at' => now(),

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Customer A mencoba mengakses invoice Customer B
        |--------------------------------------------------------------------------
        */

        $response = $this->withHeader(
            'X-Guest-Session-Id',
            $customerA->guest_token
        )->get(
            "/api/orders/{$order->id}/invoice"
        );

        /*
        |--------------------------------------------------------------------------
        | Assertions
        |--------------------------------------------------------------------------
        */

        $response->assertNotFound();

        $response->assertJson([
            'success' => false,
            'message' => 'Invoice tidak ditemukan.',
        ]);
    }

    public function test_customer_cannot_download_invoice_for_pending_order(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $customer = Customer::create([
            'name' => 'Pending Invoice Customer',
            'email' => 'pending-invoice@test.local',
            'phone' => '081234567893',
            'guest_token' => 'guest-token-pending-invoice',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Pending Order
        |--------------------------------------------------------------------------
        */

        $order = Order::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-INVOICE-PENDING',
            'midtrans_order_id' => 'MIDTRANS-INVOICE-PENDING',
            'tracking_token' => (string) \Illuminate\Support\Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 1_000_000,

            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'address' => 'Alamat Pending Invoice',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
            ],

            'shipping_method' => 'REG',
            'courier' => 'jnt_cargo',
            'total_weight' => 1,

            'status' => 'pending',
            'payment_status' => 'pending',

            'payment_expired_at' => now()->addHours(1),
            'paid_at' => null,

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Request
        |--------------------------------------------------------------------------
        */

        $response = $this->withHeader(
            'X-Guest-Session-Id',
            $customer->guest_token
        )->get(
            "/api/orders/{$order->id}/invoice"
        );

        /*
        |--------------------------------------------------------------------------
        | Assertions
        |--------------------------------------------------------------------------
        */

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Invoice belum tersedia untuk pesanan ini.',
        ]);
    }

    public function test_customer_cannot_download_invoice_without_guest_session(): void
    {
        $customer = Customer::create([
            'name' => 'No Session Customer',
            'email' => 'no-session@test.local',
            'phone' => '081234567894',
            'guest_token' => 'guest-token-no-session',
        ]);

        $order = Order::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-INVOICE-NO-SESSION',
            'midtrans_order_id' => 'MIDTRANS-INVOICE-NO-SESSION',
            'tracking_token' => (string) \Illuminate\Support\Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 1_000_000,

            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'address' => 'Alamat No Session',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
            ],

            'shipping_method' => 'REG',
            'courier' => 'jnt_cargo',
            'total_weight' => 1,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHours(1),
            'paid_at' => now(),
        ]);

        $response = $this->get(
            "/api/orders/{$order->id}/invoice"
        );

        $response->assertUnauthorized();

        $response->assertJson([
            'success' => false,
            'message' => 'Guest session tidak ditemukan.',
        ]);
    }

    public function test_customer_cannot_download_invoice_with_invalid_guest_session(): void
    {
        $customer = Customer::create([
            'name' => 'Invalid Session Customer',
            'email' => 'invalid-session@test.local',
            'phone' => '081234567895',
            'guest_token' => 'guest-token-real',
        ]);

        $order = Order::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-INVOICE-INVALID-SESSION',
            'midtrans_order_id' => 'MIDTRANS-INVOICE-INVALID-SESSION',
            'tracking_token' => (string) \Illuminate\Support\Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 1_000_000,

            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'address' => 'Alamat Invalid Session',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
            ],

            'shipping_method' => 'REG',
            'courier' => 'jnt_cargo',
            'total_weight' => 1,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHours(1),
            'paid_at' => now(),
        ]);

        $response = $this->withHeader(
            'X-Guest-Session-Id',
            'guest-token-yang-tidak-valid'
        )->get(
            "/api/orders/{$order->id}/invoice"
        );

        $response->assertUnauthorized();

        $response->assertJson([
            'success' => false,
            'message' => 'Guest session tidak valid.',
        ]);
    }
}