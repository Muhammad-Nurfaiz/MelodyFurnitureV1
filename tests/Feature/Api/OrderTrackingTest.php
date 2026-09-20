<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_track_order_using_valid_tracking_token(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $customer = Customer::create([
            'name' => 'Tracking Test Customer',
            'email' => 'tracking@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-tracking-test',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-TRACKING-001',
            'midtrans_order_id' => 'MIDTRANS-TRACKING-001',
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
                'address' => 'Jl. Tracking Test No. 1',
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

        $response = $this->getJson(
            "/api/orders/track/{$trackingToken}"
        );

        /*
        |--------------------------------------------------------------------------
        | Assertions
        |--------------------------------------------------------------------------
        */

        $response->assertOk();

        $response->assertJsonPath(
            'data.order_number',
            $order->order_number
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
            'data.customer.name',
            $customer->name
        );

        $response->assertJsonPath(
            'data.customer.email',
            $customer->email
        );

        $response->assertJsonPath(
            'data.customer.phone',
            $customer->phone
        );

        $response->assertJsonPath(
            'data.shipping.courier',
            'jnt_cargo'
        );

        $response->assertJsonPath(
            'data.shipping.service',
            'regular'
        );

        $response->assertJsonPath(
            'data.summary.subtotal',
            '1000000.00'
        );

        $response->assertJsonPath(
            'data.summary.shipping_fee',
            '25000.00'
        );

        $response->assertJsonPath(
            'data.summary.voucher_discount',
            '0.00'
        );

        $response->assertJsonPath(
            'data.summary.total_payment',
            '1025000.00'
        );
    }

    public function test_tracking_returns_not_found_for_invalid_tracking_token(): void
    {
        $response = $this->getJson(
            '/api/orders/track/tracking-token-yang-tidak-valid'
        );

        $response->assertNotFound();

        $response->assertJson([
            'success' => false,
            'message' => 'Resource not found.',
            'errors' => null,
        ]);
    }

    public function test_tracking_returns_shipment_information_when_order_has_shipment(): void
    {
        $customer = Customer::create([
            'name' => 'Shipment Tracking Customer',
            'email' => 'shipment-tracking@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-shipment-tracking-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-TRACKING-SHIPMENT-001',
            'midtrans_order_id' => 'MIDTRANS-TRACKING-SHIPMENT-001',
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
                'address' => 'Jl. Shipment Test No. 1',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65145',
            ],

            'shipping_method' => 'regular',
            'courier' => 'jnt_cargo',
            'tracking_number' => 'JNT-TEST-123456',
            'total_weight' => 1,

            'status' => 'shipped',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->subHour(),
            'paid_at' => now()->subHours(5),

            'packed_at' => now()->subHours(4),
            'picked_up_at' => now()->subHours(3),
            'shipped_at' => now()->subHours(3),
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        Shipment::create([
            'order_id' => $order->id,
            'courier' => 'jnt_cargo',
            'service' => 'regular',
            'booking_code' => 'BOOKING-TEST-001',
            'tracking_number' => 'JNT-TEST-123456',
            'label_url' => 'https://example.com/label-test.pdf',
            'status' => 'in_transit',
            'metadata' => [
                'source' => 'feature_test',
            ],
            'picked_up_at' => now()->subHours(3),
            'delivered_at' => null,
            'last_tracking_sync_at' => now()->subHour(),
        ]);

        $response = $this->getJson(
            "/api/orders/track/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJsonPath(
            'data.shipment.status',
            'in_transit'
        );

        $response->assertJsonPath(
            'data.shipment.tracking_number',
            'JNT-TEST-123456'
        );

        $response->assertJsonPath(
            'data.shipment.label_url',
            'https://example.com/label-test.pdf'
        );

        $response->assertJsonPath(
            'data.shipment.picked_up_at',
            fn ($value) => $value !== null
        );

        $response->assertJsonPath(
            'data.shipment.delivered_at',
            null
        );
    }

    public function test_tracking_returns_items_and_timeline(): void
    {
        $customer = Customer::create([
            'name' => 'Items Timeline Customer',
            'email' => 'items-timeline@test.local',
            'phone' => '081234567890',
            'guest_token' => 'guest-token-items-timeline-test',
        ]);

        $trackingToken = (string) Str::ulid();

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,

            'order_number' => 'ORD-TRACKING-ITEMS-001',
            'midtrans_order_id' => 'MIDTRANS-TRACKING-ITEMS-001',
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
                'address' => 'Jl. Items Test No. 1',
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
            'paid_at' => now(),

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        $category = Category::create([
            'name' => 'Tracking Test Category',
            'slug' => 'tracking-test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Tracking Test Product',
            'slug' => 'tracking-test-product',
            'description' => 'Product untuk test tracking.',
            'product_detail' => null,
            'original_price' => 500000,
            'discount_price' => 500000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'product_sku' => 'TRACK-001',
            'quantity' => 2,
            'unit_price' => 500000,
            'subtotal' => 1000000,
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => 'paid',
            'description' => 'Pembayaran berhasil.',
            'admin_id' => null,
            'actor' => 'system',
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => 'processing',
            'description' => 'Pesanan sedang diproses.',
            'admin_id' => null,
            'actor' => 'system',
        ]);

        $response = $this->getJson(
            "/api/orders/track/{$trackingToken}"
        );

        $response->assertOk();

        $response->assertJsonPath(
            'data.items.0.id',
            $product->id
        );

        $response->assertJsonPath(
            'data.items.0.name',
            'Tracking Test Product'
        );

        $response->assertJsonPath(
            'data.items.0.quantity',
            2
        );

        $response->assertJsonPath(
            'data.items.0.unit_price',
            '500000.00'
        );

        $response->assertJsonPath(
            'data.items.0.subtotal',
            '1000000.00'
        );

        $response->assertJsonPath(
            'data.timeline.0.status',
            'paid'
        );

        $response->assertJsonPath(
            'data.timeline.1.status',
            'processing'
        );
    }
}