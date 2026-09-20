<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\OrderCancelRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CustomerTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_track_order_using_valid_tracking_token(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Test',
            'slug' => 'furniture-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Test',
            'slug' => 'produk-test',
            'description' => 'Produk untuk pengujian tracking.',
            'original_price' => 1000000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567890',
            'email' => 'tracking@test.test',
            'name' => 'Customer Test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-TRACKING-TEST',
            'midtrans_order_id' => 'MIDTRANS-TRACKING-TEST',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1000000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 1000000,

            'shipping_address' => [
                'address' => 'Alamat Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 1,

            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_expired_at' => now()->addHours(24),
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 1,
            'unit_price' => 1000000,
            'subtotal' => 1000000,
        ]);

        Payment::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'transaction_id' => null,
            'snap_token' => null,
            'payment_type' => null,
            'transaction_status' => 'pending',
            'fraud_status' => null,
            'gross_amount' => 1000000,
            'bank' => null,
            'va_number' => null,
            'expired_at' => now()->addHours(24),
            'paid_at' => null,
            'raw_response' => null,
            'raw_notification' => null,
        ]);

        OrderStatusHistory::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'status' => 'pending',
            'description' => null,
            'admin_id' => null,
            'actor' => 'system',
        ]);

        $response = $this->getJson(
            "/api/tracking/{$trackingToken}"
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'order' => [
                        'order_number',
                        'tracking_token',
                        'status',
                        'status_label',
                        'payment_status',
                        'customer',
                        'shipping',
                        'payment',
                        'summary',
                        'items',
                        'cancellation_request',
                        'timeline',
                        'created_at',
                        'updated_at',
                    ],
                    'actions',
                ],
            ]);

        $response->assertJsonPath(
            'success',
            true
        );

        $response->assertJsonPath(
            'data.order.order_number',
            'ORD-TRACKING-TEST'
        );

        $response->assertJsonPath(
            'data.order.tracking_token',
            $trackingToken
        );

        $response->assertJsonPath(
            'data.order.status',
            'pending'
        );

        $response->assertJsonPath(
            'data.order.status_label',
            'Menunggu Pembayaran'
        );

        $response->assertJsonPath(
            'data.order.payment_status',
            'pending'
        );

        $response->assertJsonPath(
            'data.order.customer.name',
            'Customer Test'
        );

        $response->assertJsonPath(
            'data.order.items.0.name',
            'Produk Test'
        );

        $response->assertJsonPath(
            'data.order.items.0.quantity',
            1
        );

        $response->assertJsonPath(
            'data.order.timeline.0.status',
            'pending'
        );

        $response->assertJsonPath(
            'data.order.timeline.0.title',
            'Pesanan dibuat'
        );

        $response->assertJsonPath(
            'data.order.timeline.0.description',
            'Pesanan berhasil dibuat.'
        );

        $response->assertJsonPath(
            'data.actions.can_pay',
            true
        );

        $response->assertJsonPath(
            'data.actions.can_request_cancel',
            false
        );

        $response->assertJsonPath(
            'data.actions.can_track_shipping',
            false
        );

        $response->assertJsonPath(
            'data.actions.can_download_invoice',
            false
        );
    }

    public function test_customer_gets_404_when_tracking_token_is_invalid(): void
    {
        $invalidTrackingToken = (string) \Illuminate\Support\Str::ulid();

        $response = $this->getJson(
            "/api/tracking/{$invalidTrackingToken}"
        );

        $response->assertNotFound();

        $response->assertJson([
            'success' => false,
            'message' => 'Resource not found.',
            'errors' => null,
        ]);
    }

    public function test_customer_can_get_payment_information_for_pending_order(): void
    {
        $customer = Customer::create([
            'phone' => '081234567890',
            'email' => 'payment-test@example.com',
            'name' => 'Payment Test Customer',
            'address_detail' => 'Alamat Test',
            'destination_code' => '3573',
            'guest_token' => (string) Str::ulid(),
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-TEST',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-TEST',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 1_000_000,

            'shipping_address' => [
                'address' => 'Alamat Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'pending',
            'payment_status' => 'pending',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => null,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,

            'transaction_id' => 'TRX-PAYMENT-TEST',
            'snap_token' => 'SNAP-TEST-TOKEN',

            'payment_type' => 'bank_transfer',
            'transaction_status' => 'pending',
            'fraud_status' => null,

            'gross_amount' => 1_000_000,

            'bank' => null,
            'va_number' => null,

            'expired_at' => now()->addHour(),
            'paid_at' => null,

            'raw_response' => [
                'transaction_id' => null,
                'order_id' => $order->midtrans_order_id,
                'snap_token' => 'SNAP-TEST-TOKEN',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-TOKEN',
                'expiry_time' => now()->addHour()->toIso8601String(),
                'payload' => [],
            ],
            'raw_notification' => null,
        ]);

        $response = $this->getJson(
            "/api/tracking/{$trackingToken}/payment"
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'order_number',
                    'tracking_token',
                    'status',
                    'payment_status',
                    'payment_method',
                    'snap_token',
                    'redirect_url',
                    'transaction_id',
                    'total_payment',
                    'expired_at',
                    'remaining_seconds',
                    'is_expired',
                    'can_pay',
                    'paid_at',
                ],
            ]);

        $response->assertJsonPath(
            'data.order_number',
            'ORD-PAYMENT-TEST'
        );

        $response->assertJsonPath(
            'data.tracking_token',
            $trackingToken
        );

        $response->assertJsonPath(
            'data.status',
            'pending'
        );

        $response->assertJsonPath(
            'data.payment_status',
            'pending'
        );

        $response->assertJsonPath(
            'data.payment_method',
            'bank_transfer'
        );

        $response->assertJsonPath(
            'data.snap_token',
            'SNAP-TEST-TOKEN'
        );

        $response->assertJsonPath(
            'data.redirect_url',
            'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-TOKEN'
        );

        $response->assertJsonPath(
            'data.transaction_id',
            'TRX-PAYMENT-TEST'
        );

        $response->assertJsonPath(
            'data.total_payment',
            '1000000.00'
        );

        $response->assertJsonPath(
            'data.is_expired',
            false
        );

        $response->assertJsonPath(
            'data.can_pay',
            true
        );

        $response->assertJsonPath(
            'data.paid_at',
            null
        );
    }

    public function test_customer_cannot_pay_when_payment_is_expired(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567891',
            'email' => 'expired-payment@test.test',
            'name' => 'Expired Payment Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-EXPIRED',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-EXPIRED',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 500000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 500000,

            'shipping_address' => [
                'address' => 'Alamat Expired Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'pending',
            'payment_status' => 'pending',

            'payment_expired_at' => now()->subMinute(),
            'paid_at' => null,
        ]);

        Payment::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,

            'transaction_id' => 'TRX-PAYMENT-EXPIRED',
            'snap_token' => 'SNAP-EXPIRED-TOKEN',

            'payment_type' => null,
            'transaction_status' => 'pending',
            'fraud_status' => null,

            'gross_amount' => 500000,

            'bank' => null,
            'va_number' => null,

            'expired_at' => now()->subMinute(),
            'paid_at' => null,

            'raw_response' => [
                'transaction_id' => null,
                'order_id' => $order->midtrans_order_id,
                'snap_token' => 'SNAP-EXPIRED-TOKEN',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/EXPIRED-TOKEN',
                'expiry_time' => now()->subMinute()->toIso8601String(),
                'payload' => [],
            ],

            'raw_notification' => null,
        ]);

        $response = $this->getJson(
            "/api/tracking/{$trackingToken}/payment"
        );

        $response->assertOk();

        $response->assertJsonPath(
            'data.order_number',
            'ORD-PAYMENT-EXPIRED'
        );

        $response->assertJsonPath(
            'data.status',
            'pending'
        );

        $response->assertJsonPath(
            'data.payment_status',
            'pending'
        );

        $response->assertJsonPath(
            'data.is_expired',
            true
        );

        $response->assertJsonPath(
            'data.remaining_seconds',
            0
        );

        $response->assertJsonPath(
            'data.can_pay',
            false
        );
    }

    public function test_customer_cannot_pay_when_order_is_already_paid(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567892',
            'email' => 'paid-payment@test.test',
            'name' => 'Paid Payment Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-PAYMENT-PAID',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-PAID',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 750000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 750000,

            'shipping_address' => [
                'address' => 'Alamat Paid Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        Payment::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,

            'transaction_id' => 'TRX-PAYMENT-PAID',
            'snap_token' => 'SNAP-PAID-TOKEN',

            'payment_type' => 'bank_transfer',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',

            'gross_amount' => 750000,

            'bank' => 'bca',
            'va_number' => '1234567890',

            'expired_at' => now()->addHour(),
            'paid_at' => now(),

            'raw_response' => [
                'transaction_id' => 'TRX-PAYMENT-PAID',
                'order_id' => $order->midtrans_order_id,
                'snap_token' => 'SNAP-PAID-TOKEN',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/PAID-TOKEN',
                'expiry_time' => now()->addHour()->toIso8601String(),
                'payload' => [],
            ],

            'raw_notification' => null,
        ]);

        $response = $this->getJson(
            "/api/tracking/{$trackingToken}/payment"
        );

        $response->assertOk();

        $response->assertJsonPath(
            'data.order_number',
            'ORD-PAYMENT-PAID'
        );

        $response->assertJsonPath(
            'data.status',
            'paid'
        );

        $response->assertJsonPath(
            'data.payment_status',
            'paid'
        );

        $response->assertJsonPath(
            'data.payment_method',
            'bank_transfer'
        );

        $response->assertJsonPath(
            'data.transaction_id',
            'TRX-PAYMENT-PAID'
        );

        $response->assertJsonPath(
            'data.is_expired',
            false
        );

        $response->assertJsonPath(
            'data.can_pay',
            false
        );
    }

    public function test_customer_gets_404_when_payment_tracking_token_is_invalid(): void
    {
        $trackingToken = (string) Str::ulid();

        $response = $this->getJson(
            "/api/tracking/{$trackingToken}/payment"
        );

        $response->assertNotFound();

        $response->assertJson([
            'success' => false,
            'message' => 'Resource not found.',
            'errors' => null,
        ]);
    }

    public function test_customer_can_request_cancellation_for_paid_order(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567893',
            'email' => 'cancellation-test@test.test',
            'name' => 'Cancellation Test Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-TEST',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-TEST',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 1_000_000,

            'shipping_address' => [
                'address' => 'Alamat Cancellation Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        $reason = 'Saya ingin membatalkan pesanan karena berubah pikiran.';

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            [
                'reason' => $reason,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'order' => [
                        'id',
                        'order_number',
                        'status',
                    ],
                    'cancellation_request' => [
                        'id',
                        'reason',
                        'previous_status',
                        'status',
                        'created_at',
                    ],
                ],
            ]);

        $response->assertJsonPath(
            'success',
            true
        );

        $response->assertJsonPath(
            'message',
            'Permintaan pembatalan berhasil dikirim.'
        );

        $response->assertJsonPath(
            'data.order.id',
            $order->id
        );

        $response->assertJsonPath(
            'data.order.order_number',
            'ORD-CANCELLATION-TEST'
        );

        $response->assertJsonPath(
            'data.order.status',
            'req_cancel'
        );

        $response->assertJsonPath(
            'data.cancellation_request.reason',
            $reason
        );

        $response->assertJsonPath(
            'data.cancellation_request.previous_status',
            'paid'
        );

        $response->assertJsonPath(
            'data.cancellation_request.status',
            'pending'
        );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'req_cancel',
        ]);

        $this->assertDatabaseHas('order_cancel_requests', [
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'reason' => $reason,
            'previous_status' => 'paid',
            'status' => 'pending',
        ]);
    }

    public function test_customer_cannot_request_cancellation_for_pending_order(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567894',
            'email' => 'cancellation-pending@test.test',
            'name' => 'Cancellation Pending Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-PENDING',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-PENDING',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 500_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 500_000,

            'shipping_address' => [
                'address' => 'Alamat Pending Cancellation Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'pending',
            'payment_status' => 'pending',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => null,
        ]);

        $reason = 'Saya ingin membatalkan pesanan ini.';

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            [
                'reason' => $reason,
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Order hanya dapat mengajukan pembatalan saat berstatus paid atau processing.',
                'errors' => null,
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseMissing('order_cancel_requests', [
            'order_id' => $order->id,
        ]);
    }

    public function test_customer_can_request_cancellation_for_processing_order(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567895',
            'email' => 'cancellation-processing@test.test',
            'name' => 'Cancellation Processing Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-PROCESSING',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-PROCESSING',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 1_500_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 1_500_000,

            'shipping_address' => [
                'address' => 'Alamat Processing Cancellation Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'processing',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        $reason = 'Saya ingin membatalkan pesanan yang sedang diproses.';

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            [
                'reason' => $reason,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'order' => [
                        'id',
                        'order_number',
                        'status',
                    ],
                    'cancellation_request' => [
                        'id',
                        'reason',
                        'previous_status',
                        'status',
                        'created_at',
                    ],
                ],
            ]);

        $response->assertJsonPath(
            'success',
            true
        );

        $response->assertJsonPath(
            'data.order.id',
            $order->id
        );

        $response->assertJsonPath(
            'data.order.order_number',
            'ORD-CANCELLATION-PROCESSING'
        );

        $response->assertJsonPath(
            'data.order.status',
            'req_cancel'
        );

        $response->assertJsonPath(
            'data.cancellation_request.reason',
            $reason
        );

        $response->assertJsonPath(
            'data.cancellation_request.previous_status',
            'processing'
        );

        $response->assertJsonPath(
            'data.cancellation_request.status',
            'pending'
        );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'req_cancel',
        ]);

        $this->assertDatabaseHas('order_cancel_requests', [
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'reason' => $reason,
            'previous_status' => 'processing',
            'status' => 'pending',
        ]);
    }

    public function test_customer_cannot_create_duplicate_pending_cancellation_request(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567896',
            'email' => 'cancellation-duplicate@test.test',
            'name' => 'Cancellation Duplicate Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-DUPLICATE',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-DUPLICATE',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 800_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 800_000,

            'shipping_address' => [
                'address' => 'Alamat Duplicate Cancellation Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        OrderCancelRequest::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'reason' => 'Permintaan pembatalan sebelumnya.',
            'previous_status' => 'paid',
            'status' => 'pending',
        ]);

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            [
                'reason' => 'Saya mengajukan pembatalan sekali lagi.',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Permintaan pembatalan untuk order ini masih menunggu persetujuan.',
                'errors' => null,
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseCount(
            'order_cancel_requests',
            1
        );

        $this->assertDatabaseHas('order_cancel_requests', [
            'order_id' => $order->id,
            'reason' => 'Permintaan pembatalan sebelumnya.',
            'previous_status' => 'paid',
            'status' => 'pending',
        ]);
    }

    public function test_customer_gets_404_when_cancellation_tracking_token_is_invalid(): void
    {
        $invalidTrackingToken = (string) Str::ulid();

        $response = $this->postJson(
            "/api/tracking/{$invalidTrackingToken}/cancellation",
            [
                'reason' => 'Saya ingin membatalkan pesanan ini.',
            ]
        );

        $response
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Order tidak ditemukan.',
                'errors' => null,
            ]);

        $this->assertDatabaseCount(
            'order_cancel_requests',
            0
        );
    }

    public function test_customer_cannot_request_cancellation_without_reason(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567897',
            'email' => 'cancellation-validation@test.test',
            'name' => 'Cancellation Validation Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-VALIDATION',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-VALIDATION',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 500_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 500_000,

            'shipping_address' => [
                'address' => 'Alamat Validation Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            []
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'reason',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseCount(
            'order_cancel_requests',
            0
        );
    }

    public function test_customer_cannot_request_cancellation_with_reason_less_than_5_characters(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567898',
            'email' => 'cancellation-min@test.test',
            'name' => 'Cancellation Min Validation Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-MIN',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-MIN',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 500_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 500_000,

            'shipping_address' => [
                'address' => 'Alamat Minimum Validation Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            [
                'reason' => 'btl',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'reason',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseCount(
            'order_cancel_requests',
            0
        );
    }

    public function test_customer_cannot_request_cancellation_with_reason_more_than_500_characters(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567899',
            'email' => 'cancellation-max@test.test',
            'name' => 'Cancellation Max Validation Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-MAX',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-MAX',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 500_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 500_000,

            'shipping_address' => [
                'address' => 'Alamat Maximum Validation Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            [
                'reason' => str_repeat('a', 501),
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'reason',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseCount(
            'order_cancel_requests',
            0
        );
    }

    public function test_customer_cannot_request_cancellation_with_non_string_reason(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567800',
            'email' => 'cancellation-type@test.test',
            'name' => 'Cancellation Type Validation Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-TYPE',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-TYPE',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 500_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 500_000,

            'shipping_address' => [
                'address' => 'Alamat Type Validation Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            [
                'reason' => 12345,
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'reason',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseCount(
            'order_cancel_requests',
            0
        );
    }

    public function test_customer_can_request_cancellation_with_reason_of_exactly_5_characters(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567801',
            'email' => 'cancellation-exact-min@test.test',
            'name' => 'Cancellation Exact Min Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-EXACT-MIN',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-EXACT-MIN',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 500_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 500_000,

            'shipping_address' => [
                'address' => 'Alamat Exact Minimum Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            [
                'reason' => 'Batal',
            ]
        );

        $response
            ->assertStatus(201)
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.cancellation_request.reason',
                'Batal'
            )
            ->assertJsonPath(
                'data.cancellation_request.previous_status',
                'paid'
            )
            ->assertJsonPath(
                'data.cancellation_request.status',
                'pending'
            );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'req_cancel',
        ]);

        $this->assertDatabaseHas('order_cancel_requests', [
            'order_id' => $order->id,
            'reason' => 'Batal',
            'previous_status' => 'paid',
            'status' => 'pending',
        ]);
    }

    public function test_customer_can_request_cancellation_with_reason_of_exactly_500_characters(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567802',
            'email' => 'cancellation-exact-max@test.test',
            'name' => 'Cancellation Exact Max Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-EXACT-MAX',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-EXACT-MAX',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 500_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 500_000,

            'shipping_address' => [
                'address' => 'Alamat Exact Maximum Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        $reason = str_repeat('a', 500);

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            [
                'reason' => $reason,
            ]
        );

        $response
            ->assertStatus(201)
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.cancellation_request.reason',
                $reason
            )
            ->assertJsonPath(
                'data.cancellation_request.previous_status',
                'paid'
            )
            ->assertJsonPath(
                'data.cancellation_request.status',
                'pending'
            );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'req_cancel',
        ]);

        $this->assertDatabaseHas('order_cancel_requests', [
            'order_id' => $order->id,
            'reason' => $reason,
            'previous_status' => 'paid',
            'status' => 'pending',
        ]);
    }

    public function test_customer_cannot_request_cancellation_for_cancelled_order(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567803',
            'email' => 'cancellation-cancelled@test.test',
            'name' => 'Cancelled Order Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-CANCELLED',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-CANCELLED',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 500_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 500_000,

            'shipping_address' => [
                'address' => 'Alamat Cancelled Order Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'cancelled',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            [
                'reason' => 'Saya ingin membatalkan pesanan.',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Order hanya dapat mengajukan pembatalan saat berstatus paid atau processing.',
                'errors' => null,
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseCount(
            'order_cancel_requests',
            0
        );
    }

    public function test_customer_cannot_request_cancellation_for_completed_order(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567804',
            'email' => 'cancellation-completed@test.test',
            'name' => 'Completed Order Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-COMPLETED',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-COMPLETED',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 500_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 500_000,

            'shipping_address' => [
                'address' => 'Alamat Completed Order Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'completed',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            [
                'reason' => 'Saya ingin membatalkan pesanan.',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Order hanya dapat mengajukan pembatalan saat berstatus paid atau processing.',
                'errors' => null,
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseCount(
            'order_cancel_requests',
            0
        );
    }

    public function test_customer_cannot_request_cancellation_for_order_already_waiting_for_cancellation(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567805',
            'email' => 'cancellation-req-cancel@test.test',
            'name' => 'Request Cancellation Order Customer',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-CANCELLATION-REQ-CANCEL',
            'midtrans_order_id' => 'MIDTRANS-CANCELLATION-REQ-CANCEL',
            'tracking_token' => $trackingToken,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 500_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 500_000,

            'shipping_address' => [
                'address' => 'Alamat Req Cancel Test',
                'destination_code' => '3573',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 0,

            'status' => 'req_cancel',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        OrderCancelRequest::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'reason' => 'Permintaan pembatalan sedang diproses.',
            'previous_status' => 'paid',
            'status' => 'pending',
        ]);

        $response = $this->postJson(
            "/api/tracking/{$trackingToken}/cancellation",
            [
                'reason' => 'Saya ingin membatalkan lagi.',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Order hanya dapat mengajukan pembatalan saat berstatus paid atau processing.',
                'errors' => null,
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'req_cancel',
        ]);

        $this->assertDatabaseCount(
            'order_cancel_requests',
            1
        );
    }
}