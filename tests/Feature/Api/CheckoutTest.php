<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\ShippingCourier;
use App\Models\ShippingRate;
use App\Models\Voucher;
use App\Services\Payment\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_checkout_without_email(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Province & Regency
        |--------------------------------------------------------------------------
        */

        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Shipping Courier & Rate
        |--------------------------------------------------------------------------
        */

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Product
        |--------------------------------------------------------------------------
        */

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Checkout Product',
            'slug' => 'test-checkout-product',
            'description' => 'Product khusus untuk Feature Test checkout.',
            'product_detail' => null,
            'original_price' => 1515000,
            'discount_price' => 1203000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 100,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '159 x 39.6 x 48 cm',
            'weight' => 32.50,
            'packing_weight' => 34.50,
            'load_capacity' => '100 kg',
            'assembly_required' => false,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Guest Customer & Cart
        |--------------------------------------------------------------------------
        */

        $guestToken = 'test-guest-token-checkout';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Mock Midtrans
        |--------------------------------------------------------------------------
        */

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->once()
                ->andReturnUsing(function (Order $order) {
                    return [
                        'transaction_id' => null,
                        'order_id' => $order->midtrans_order_id,
                        'snap_token' => 'TEST-SNAP-TOKEN',
                        'redirect_url' =>
                            'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-SNAP-TOKEN',
                        'expiry_time' => $order->payment_expired_at,
                        'payload' => [],
                    ];
                });
        });

        /*
        |--------------------------------------------------------------------------
        | Checkout
        |--------------------------------------------------------------------------
        */

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Test Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Test Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        /*
        |--------------------------------------------------------------------------
        | HTTP Response
        |--------------------------------------------------------------------------
        */

        $response->assertCreated();

        $response->assertJsonPath(
            'message',
            'Checkout berhasil.'
        );

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */

        $order = Order::query()
            ->with([
                'items',
                'payment',
            ])
            ->latest('created_at')
            ->first();

        $this->assertNotNull($order);

        $this->assertSame(
            'Test Customer',
            $order->customer_name
        );

        $this->assertNull(
            $order->customer_email
        );

        $this->assertSame(
            '081234567890',
            $order->customer_phone
        );

        $this->assertSame(
            'pending',
            $order->status
        );

        $this->assertSame(
            'pending',
            $order->payment_status
        );

        /*
        |--------------------------------------------------------------------------
        | Price Calculation
        |--------------------------------------------------------------------------
        |
        | Product:
        | Rp1.203.000
        |
        | Packing weight:
        | 34.5 kg -> ceil = 35 kg
        |
        | J&T:
        | 35 x Rp11.840 = Rp414.400
        |
        | Total:
        | Rp1.203.000 + Rp414.400 = Rp1.617.400
        |
        */

        $this->assertEquals(
            1203000,
            (float) $order->total_product_price
        );

        $this->assertEquals(
            34.50,
            (float) $order->total_weight
        );

        $this->assertEquals(
            414400,
            (float) $order->shipping_fee
        );

        $this->assertEquals(
            1617400,
            (float) $order->total_payment
        );

        /*
        |--------------------------------------------------------------------------
        | Order Item
        |--------------------------------------------------------------------------
        */

        $this->assertCount(
            1,
            $order->items
        );

        $orderItem = $order->items->first();

        $this->assertSame(
            $product->id,
            $orderItem->product_id
        );

        $this->assertSame(
            1,
            $orderItem->quantity
        );

        $this->assertEquals(
            1203000,
            (float) $orderItem->unit_price
        );

        $this->assertEquals(
            1203000,
            (float) $orderItem->subtotal
        );

        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        */

        $this->assertNotNull(
            $order->payment
        );

        $this->assertSame(
            'TEST-SNAP-TOKEN',
            $order->payment->snap_token
        );

        $this->assertSame(
            'pending',
            $order->payment->transaction_status
        );

        $this->assertEquals(
            1617400,
            (float) $order->payment->gross_amount
        );

        /*
        |--------------------------------------------------------------------------
        | Stock
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 99,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cart
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);

        $this->assertDatabaseCount(
            'cart_items',
            0
        );
    }

    public function test_customer_can_checkout_selected_items_only(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Selected Checkout Category',
            'slug' => 'selected-checkout-category',
        ]);

        $productSelected = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Selected Checkout Product',
            'slug' => 'selected-checkout-product',
            'description' => 'Produk yang dipilih untuk checkout.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $productSelected->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $productNotSelected = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Not Selected Checkout Product',
            'slug' => 'not-selected-checkout-product',
            'description' => 'Produk yang tidak dipilih untuk checkout.',
            'product_detail' => null,
            'original_price' => 600000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 20,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $productNotSelected->id,
            'dimensions' => '80 x 40 x 40 cm',
            'weight' => 8,
            'packing_weight' => 9,
            'load_capacity' => '30 kg',
            'assembly_required' => false,
        ]);

        $guestToken = 'test-guest-token-selected-checkout';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $selectedCartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $productSelected->id,
            'quantity' => 1,
        ]);

        $notSelectedCartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $productNotSelected->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->once()
                ->andReturnUsing(function (Order $order) {
                    return [
                        'transaction_id' => null,
                        'order_id' => $order->midtrans_order_id,
                        'snap_token' => 'TEST-SELECTED-SNAP-TOKEN',
                        'redirect_url' =>
                            'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-SELECTED-SNAP-TOKEN',
                        'expiry_time' => $order->payment_expired_at,
                        'payload' => [],
                    ];
                });
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Selected Checkout Customer',
                'phone' => '081234567891',
                'courier' => 'jnt_cargo',
                'selected_item_ids' => [
                    $selectedCartItem->id,
                ],
                'shipping_address' => [
                    'recipient_name' => 'Selected Checkout Customer',
                    'phone' => '081234567891',
                    'regency_id' => '1102',
                    'address' => 'Jl. Selected Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertCreated();
        $response->assertJsonPath(
            'message',
            'Checkout berhasil.'
        );

        $order = Order::query()
            ->with(['items', 'payment'])
            ->latest('created_at')
            ->first();

        $this->assertNotNull($order);

        // Hanya produk yang dipilih yang masuk order.
        $this->assertCount(1, $order->items);

        $orderItem = $order->items->first();

        $this->assertSame(
            $productSelected->id,
            $orderItem->product_id
        );

        $this->assertSame(1, $orderItem->quantity);

        $this->assertEquals(
            800000,
            (float) $orderItem->unit_price
        );

        $this->assertEquals(
            800000,
            (float) $orderItem->subtotal
        );

        // Berat 12 kg -> ongkir 12 x 11.840.
        $this->assertEquals(
            12,
            (float) $order->total_weight
        );

        $this->assertEquals(
            142080,
            (float) $order->shipping_fee
        );

        $this->assertEquals(
            942080,
            (float) $order->total_payment
        );

        // Payment menggunakan mock Midtrans.
        $this->assertNotNull($order->payment);

        $this->assertSame(
            'TEST-SELECTED-SNAP-TOKEN',
            $order->payment->snap_token
        );

        // Stok hanya produk yang dipilih yang berkurang.
        $this->assertDatabaseHas('products', [
            'id' => $productSelected->id,
            'ready_stock' => 9,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $productNotSelected->id,
            'ready_stock' => 20,
        ]);

        // Item yang dipilih dihapus dari cart.
        $this->assertDatabaseMissing('cart_items', [
            'id' => $selectedCartItem->id,
        ]);

        // Item yang tidak dipilih tetap berada di cart.
        $this->assertDatabaseHas('cart_items', [
            'id' => $notSelectedCartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $productNotSelected->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_customer_can_checkout_with_valid_voucher(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Voucher Checkout Category',
            'slug' => 'voucher-checkout-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Voucher Checkout Product',
            'slug' => 'voucher-checkout-product',
            'description' => 'Produk khusus untuk test voucher checkout.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $voucher = \App\Models\Voucher::create([
            'code' => 'TEST100K',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'min_order_amount' => 500000,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $guestToken = 'test-guest-token-voucher-checkout';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->once()
                ->andReturnUsing(function (Order $order) {
                    return [
                        'transaction_id' => null,
                        'order_id' => $order->midtrans_order_id,
                        'snap_token' => 'TEST-VOUCHER-SNAP-TOKEN',
                        'redirect_url' =>
                            'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-VOUCHER-SNAP-TOKEN',
                        'expiry_time' => $order->payment_expired_at,
                        'payload' => [],
                    ];
                });
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Voucher Checkout Customer',
                'phone' => '081234567892',
                'voucher_code' => 'TEST100K',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Voucher Checkout Customer',
                    'phone' => '081234567892',
                    'regency_id' => '1102',
                    'address' => 'Jl. Voucher Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertCreated();

        $response->assertJsonPath(
            'message',
            'Checkout berhasil.'
        );

        $order = Order::query()
            ->with(['items', 'payment'])
            ->latest('created_at')
            ->first();

        $this->assertNotNull($order);

        // Voucher harus terhubung ke order.
        $this->assertSame(
            $voucher->id,
            $order->voucher_id
        );

        // Subtotal produk.
        $this->assertEquals(
            800000,
            (float) $order->total_product_price
        );

        // Diskon voucher fixed Rp100.000.
        $this->assertEquals(
            100000,
            (float) $order->voucher_discount_amount
        );

        // Berat 12 kg.
        $this->assertEquals(
            12,
            (float) $order->total_weight
        );

        // Ongkir = 12 x Rp11.840.
        $this->assertEquals(
            142080,
            (float) $order->shipping_fee
        );

        // 800.000 - 100.000 + 142.080.
        $this->assertEquals(
            842080,
            (float) $order->total_payment
        );

        // Payment tetap dibuat.
        $this->assertNotNull($order->payment);

        $this->assertSame(
            'TEST-VOUCHER-SNAP-TOKEN',
            $order->payment->snap_token
        );

        // Stok berkurang.
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 9,
        ]);

        // Cart dibersihkan setelah checkout.
        $this->assertDatabaseCount(
            'cart_items',
            0
        );
    }

    public function test_checkout_fails_when_cart_is_empty(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $guestToken = 'test-guest-token-empty-cart';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Empty Cart Customer',
                'phone' => '081234567893',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Empty Cart Customer',
                    'phone' => '081234567893',
                    'regency_id' => '1102',
                    'address' => 'Jl. Empty Cart No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Cart kosong.',
            'errors' => null,
        ]);
    }

    public function test_checkout_fails_when_stock_is_insufficient(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Insufficient Stock Category',
            'slug' => 'insufficient-stock-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Insufficient Stock Product',
            'slug' => 'insufficient-stock-product',
            'description' => 'Produk untuk test stok tidak mencukupi.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 2,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $guestToken = 'test-guest-token-insufficient-stock';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Insufficient Stock Customer',
                'phone' => '081234567894',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Insufficient Stock Customer',
                    'phone' => '081234567894',
                    'regency_id' => '1102',
                    'address' => 'Jl. Stock Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Validation failed.',
        ]);

        $response->assertJsonPath(
            'errors.stock.0',
            'Stok Insufficient Stock Product tidak mencukupi.'
        );

        // Tidak boleh ada order yang tercipta.
        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseCount('order_items', 0);

        // Stok tetap 2.
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 2,
        ]);

        // Cart tetap ada karena checkout gagal.
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_checkout_fails_when_shipping_rate_is_not_found(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        // Courier ada, tetapi tidak memiliki shipping rate
        // untuk regency 1102.
        ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Shipping Rate Failure Category',
            'slug' => 'shipping-rate-failure-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Rate Failure Product',
            'slug' => 'shipping-rate-failure-product',
            'description' => 'Produk untuk test shipping rate tidak ditemukan.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $guestToken = 'test-guest-token-shipping-rate-failure';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Midtrans tidak seharusnya dipanggil karena
        // shipping calculation gagal lebih dahulu.
        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->never();
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Shipping Rate Failure Customer',
                'phone' => '081234567895',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Shipping Rate Failure Customer',
                    'phone' => '081234567895',
                    'regency_id' => '1102',
                    'address' => 'Jl. Shipping Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' =>
                'Tarif pengiriman untuk regency 1102 dan courier jnt_cargo tidak ditemukan.',
            'errors' => null,
        ]);

        // Tidak boleh ada order.
        $this->assertDatabaseCount('orders', 0);

        // Tidak boleh ada order item.
        $this->assertDatabaseCount('order_items', 0);

        // Stok tidak boleh berkurang.
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);

        // Cart tetap utuh.
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_checkout_rejects_unknown_voucher_code(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Unknown Voucher Category',
            'slug' => 'unknown-voucher-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Unknown Voucher Product',
            'slug' => 'unknown-voucher-product',
            'description' => 'Produk untuk test voucher tidak ditemukan.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $guestToken = 'test-guest-token-unknown-voucher';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Jika voucher tidak valid, Midtrans seharusnya tidak dipanggil.
        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->never();
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Unknown Voucher Customer',
                'phone' => '081234567896',
                'voucher_code' => 'VOUCHER-TIDAK-ADA',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Unknown Voucher Customer',
                    'phone' => '081234567896',
                    'regency_id' => '1102',
                    'address' => 'Jl. Voucher Unknown No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Validation failed.',
        ]);

        $response->assertJsonPath(
            'errors.voucher.0',
            'Voucher tidak ditemukan.'
        );

        // Tidak boleh ada order.
        $this->assertDatabaseCount('orders', 0);

        // Tidak boleh ada order item.
        $this->assertDatabaseCount('order_items', 0);

        // Stok tetap.
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);

        // Cart tetap.
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_checkout_fails_when_voucher_is_inactive(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Inactive Voucher Category',
            'slug' => 'inactive-voucher-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Inactive Voucher Product',
            'slug' => 'inactive-voucher-product',
            'description' => 'Produk untuk test voucher tidak aktif.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        Voucher::create([
            'code' => 'INACTIVE-VOUCHER',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => false,
        ]);

        $guestToken = 'test-guest-token-inactive-voucher';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->never();
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Inactive Voucher Customer',
                'phone' => '081234567897',
                'voucher_code' => 'INACTIVE-VOUCHER',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Inactive Voucher Customer',
                    'phone' => '081234567897',
                    'regency_id' => '1102',
                    'address' => 'Jl. Voucher Inactive No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Validation failed.',
        ]);

        $response->assertJsonPath(
            'errors.voucher.0',
            'Voucher tidak aktif.'
        );

        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_checkout_fails_when_voucher_has_not_started(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Future Voucher Category',
            'slug' => 'future-voucher-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Future Voucher Product',
            'slug' => 'future-voucher-product',
            'description' => 'Produk untuk test voucher belum mulai.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        Voucher::create([
            'code' => 'FUTURE-VOUCHER',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->addDay(),
            'expiry_date' => now()->addDays(7),
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $guestToken = 'test-guest-token-future-voucher';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->never();
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Future Voucher Customer',
                'phone' => '081234567898',
                'voucher_code' => 'FUTURE-VOUCHER',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Future Voucher Customer',
                    'phone' => '081234567898',
                    'regency_id' => '1102',
                    'address' => 'Jl. Future Voucher No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Validation failed.',
        ]);

        $response->assertJsonPath(
            'errors.voucher.0',
            'Voucher belum mulai berlaku.'
        );

        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_checkout_fails_when_voucher_has_expired(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Expired Voucher Category',
            'slug' => 'expired-voucher-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Expired Voucher Product',
            'slug' => 'expired-voucher-product',
            'description' => 'Produk untuk test voucher expired.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        Voucher::create([
            'code' => 'EXPIRED-VOUCHER',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDays(7),
            'expiry_date' => now()->subDay(),
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $guestToken = 'test-guest-token-expired-voucher';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->never();
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Expired Voucher Customer',
                'phone' => '081234567899',
                'voucher_code' => 'EXPIRED-VOUCHER',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Expired Voucher Customer',
                    'phone' => '081234567899',
                    'regency_id' => '1102',
                    'address' => 'Jl. Expired Voucher No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Validation failed.',
        ]);

        $response->assertJsonPath(
            'errors.voucher.0',
            'Voucher sudah expired.'
        );

        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_checkout_fails_when_voucher_minimum_order_is_not_met(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Minimum Voucher Category',
            'slug' => 'minimum-voucher-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Minimum Voucher Product',
            'slug' => 'minimum-voucher-product',
            'description' => 'Produk untuk test minimum order voucher.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        Voucher::create([
            'code' => 'MINIMUM-VOUCHER',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'min_order_amount' => 1000000,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $guestToken = 'test-guest-token-minimum-voucher';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->never();
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Minimum Voucher Customer',
                'phone' => '081234567800',
                'voucher_code' => 'MINIMUM-VOUCHER',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Minimum Voucher Customer',
                    'phone' => '081234567800',
                    'regency_id' => '1102',
                    'address' => 'Jl. Minimum Voucher No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Validation failed.',
        ]);

        $response->assertJsonPath(
            'errors.voucher.0',
            'Minimal pembelian belum memenuhi.'
        );

        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_checkout_fails_when_voucher_usage_limit_is_reached(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Usage Limit Voucher Category',
            'slug' => 'usage-limit-voucher-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Usage Limit Voucher Product',
            'slug' => 'usage-limit-voucher-product',
            'description' => 'Produk untuk test usage limit voucher.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        Voucher::create([
            'code' => 'LIMIT-VOUCHER',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 10,
            'is_active' => true,
        ]);

        $guestToken = 'test-guest-token-limit-voucher';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->never();
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Limit Voucher Customer',
                'phone' => '081234567801',
                'voucher_code' => 'LIMIT-VOUCHER',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Limit Voucher Customer',
                    'phone' => '081234567801',
                    'regency_id' => '1102',
                    'address' => 'Jl. Limit Voucher No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Validation failed.',
        ]);

        $response->assertJsonPath(
            'errors.voucher.0',
            'Batas penggunaan voucher sudah tercapai.'
        );

        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_checkout_applies_percentage_voucher_with_max_discount(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Percentage Voucher Category',
            'slug' => 'percentage-voucher-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Percentage Voucher Product',
            'slug' => 'percentage-voucher-product',
            'description' => 'Produk untuk test voucher persentase.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        Voucher::create([
            'code' => 'PERCENT10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'max_discount_amount' => 50000,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $guestToken = 'test-guest-token-percentage-voucher';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->once()
                ->andReturn([
                    'transaction_id' => 'TEST-PERCENTAGE-TRANSACTION',
                    'order_id' => 'TEST-PERCENTAGE-ORDER',
                    'snap_token' => 'TEST-PERCENTAGE-SNAP-TOKEN',
                    'redirect_url' => 'https://example.test/payment',
                    'expiry_time' => now()->addDay(),
                    'payload' => [],
                ]);
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Percentage Voucher Customer',
                'phone' => '081234567802',
                'voucher_code' => 'PERCENT10',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Percentage Voucher Customer',
                    'phone' => '081234567802',
                    'regency_id' => '1102',
                    'address' => 'Jl. Percentage Voucher No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(201);

        $response->assertJsonPath(
            'message',
            'Checkout berhasil.'
        );

        $response->assertJsonPath(
            'data.subtotal',
            '800000.00'
        );

        $response->assertJsonPath(
            'data.voucher_discount',
            '50000.00'
        );

        $response->assertJsonPath(
            'data.shipping_fee',
            '142080.00'
        );

        $response->assertJsonPath(
            'data.total_payment',
            '892080.00'
        );

        $this->assertDatabaseHas('orders', [
            'voucher_id' => Voucher::where('code', 'PERCENT10')->first()->id,
            'total_product_price' => 800000,
            'voucher_discount_amount' => 50000,
            'shipping_fee' => 142080,
            'total_payment' => 892080,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 9,
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_checkout_caps_fixed_voucher_discount_at_subtotal(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Fixed Voucher Category',
            'slug' => 'fixed-voucher-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Fixed Voucher Product',
            'slug' => 'fixed-voucher-product',
            'description' => 'Produk untuk test fixed voucher.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        Voucher::create([
            'code' => 'FIXED-BIG',
            'discount_type' => 'fixed',
            'discount_value' => 1000000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $guestToken = 'test-guest-token-fixed-voucher';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->once()
                ->andReturn([
                    'transaction_id' => 'TEST-FIXED-BIG-TRANSACTION',
                    'order_id' => 'TEST-FIXED-BIG-ORDER',
                    'snap_token' => 'TEST-FIXED-BIG-SNAP-TOKEN',
                    'redirect_url' => 'https://example.test/payment',
                    'expiry_time' => now()->addDay(),
                    'payload' => [],
                ]);
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $guestToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Fixed Voucher Customer',
                'phone' => '081234567803',
                'voucher_code' => 'FIXED-BIG',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Fixed Voucher Customer',
                    'phone' => '081234567803',
                    'regency_id' => '1102',
                    'address' => 'Jl. Fixed Voucher No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(201);

        $response->assertJsonPath(
            'message',
            'Checkout berhasil.'
        );

        $response->assertJsonPath(
            'data.subtotal',
            '800000.00'
        );

        $response->assertJsonPath(
            'data.voucher_discount',
            '720000.00'
        );

        $response->assertJsonPath(
            'data.shipping_fee',
            '142080.00'
        );

        $response->assertJsonPath(
            'data.total_payment',
            '222080.00'
        );

        $this->assertDatabaseHas('orders', [
            'voucher_id' => Voucher::where('code', 'FIXED-BIG')->first()->id,
            'total_product_price' => 800000,
            'voucher_discount_amount' => 720000,
            'shipping_fee' => 142080,
            'total_payment' => 222080,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 9,
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_checkout_rejects_selected_cart_item_belonging_to_another_customer(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Selected Item Security Category',
            'slug' => 'selected-item-security-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Selected Item Security Product',
            'slug' => 'selected-item-security-product',
            'description' => 'Produk untuk test isolasi selected cart item.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => 800000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        /*
        * Customer A = customer yang melakukan checkout.
        */
        $customerAToken = 'test-guest-token-customer-a';

        $customerA = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $customerAToken,
        ]);

        $cartA = Cart::create([
            'customer_id' => $customerA->id,
        ]);

        $customerACartItem = CartItem::create([
            'cart_id' => $cartA->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        /*
        * Customer B = pemilik cart item yang akan dicoba
        * digunakan oleh Customer A.
        */
        $customerB = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => 'test-guest-token-customer-b',
        ]);

        $cartB = Cart::create([
            'customer_id' => $customerB->id,
        ]);

        $customerBCartItem = CartItem::create([
            'cart_id' => $cartB->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->never();
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') => $customerAToken,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Customer A',
                'phone' => '081234567804',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Customer A',
                    'phone' => '081234567804',
                    'regency_id' => '1102',
                    'address' => 'Jl. Security Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
                'selected_item_ids' => [
                    $customerBCartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Item yang dipilih tidak ditemukan di keranjang.',
        ]);

        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $customerBCartItem->id,
            'cart_id' => $cartB->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_checkout_rejects_nonexistent_selected_cart_item_id(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'service_type' => 'regular',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Validation Selected Item Product',
            'slug' => 'validation-selected-item-product',
            'description' => 'Produk untuk pengujian validasi selected cart item.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-validation-selected-item',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $nonExistentCartItemId = (string) Str::uuid();

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Test Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Test Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $nonExistentCartItemId,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'selected_item_ids.0',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_invalid_selected_cart_item_id_format(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Invalid Selected Item Product',
            'slug' => 'invalid-selected-item-product',
            'description' => 'Produk untuk pengujian format selected cart item.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-invalid-selected-item',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Test Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Test Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    'bukan-uuid',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'selected_item_ids.0',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_with_empty_selected_item_ids_checks_out_all_cart_items(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $productA = Product::create([
            'category_id' => $category->id,
            'name' => 'Empty Selection Product A',
            'slug' => 'empty-selection-product-a',
            'description' => 'Produk A untuk pengujian selected item kosong.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        $productB = Product::create([
            'category_id' => $category->id,
            'name' => 'Empty Selection Product B',
            'slug' => 'empty-selection-product-b',
            'description' => 'Produk B untuk pengujian selected item kosong.',
            'original_price' => 500000,
            'discount_price' => null,
            'ready_stock' => 20,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $productA->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        ProductSpecification::create([
            'product_id' => $productB->id,
            'dimensions' => '80 x 40 x 40 cm',
            'weight' => 8,
            'packing_weight' => 10,
            'load_capacity' => '40 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-empty-selected-items',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItemA = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $productA->id,
            'quantity' => 1,
        ]);

        $cartItemB = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $productB->id,
            'quantity' => 2,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldReceive('createTransaction')
                ->once()
                ->andReturn([
                    'transaction_id' => null,
                    'order_id' => 'TEST-EMPTY-SELECTED-ORDER',
                    'snap_token' => 'TEST-EMPTY-SELECTED-SNAP-TOKEN',
                    'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-EMPTY-SELECTED-SNAP-TOKEN',
                    'expiry_time' => now()->addHours(24),
                    'payload' => [],
                ]);
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Test Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(201);

        $response->assertJsonPath(
            'message',
            'Checkout berhasil.'
        );

        $response->assertJsonPath(
            'data.subtotal',
            '1800000.00'
        );

        $response->assertJsonPath(
            'data.shipping_fee',
            '378880.00'
        );

        $response->assertJsonPath(
            'data.total_payment',
            '2178880.00'
        );

        $this->assertDatabaseCount('orders', 1);

        $this->assertDatabaseCount('order_items', 2);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $productA->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $productB->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $productA->id,
            'ready_stock' => 9,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $productB->id,
            'ready_stock' => 18,
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItemA->id,
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItemB->id,
        ]);
    }

    public function test_checkout_rejects_when_selected_item_ids_contains_existing_and_nonexistent_id(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Mixed Selected Item Product',
            'slug' => 'mixed-selected-item-product',
            'description' => 'Produk untuk pengujian selected item campuran.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-mixed-selected-items',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $validCartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $nonExistentCartItemId = (string) Str::uuid();

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Test Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Test Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $validCartItem->id,
                    $nonExistentCartItemId,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'selected_item_ids.1',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $validCartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_missing_customer_name(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Missing Name Product',
            'slug' => 'missing-name-product',
            'description' => 'Produk untuk pengujian nama customer.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-missing-name',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'name',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_customer_name_longer_than_100_characters(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Long Name Product',
            'slug' => 'long-name-product',
            'description' => 'Produk untuk pengujian panjang nama customer.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-long-name',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $longName = str_repeat('A', 101);

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => $longName,
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'name',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_missing_customer_phone(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Phone Validation Product',
            'slug' => 'phone-validation-product',
            'description' => 'Produk untuk pengujian validasi nomor telepon.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-missing-phone',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,

                // phone sengaja tidak dikirim

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'phone',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_customer_phone_longer_than_30_characters(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Phone Length Product',
            'slug' => 'phone-length-product',
            'description' => 'Produk untuk pengujian panjang nomor telepon.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-long-phone',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $longPhone = str_repeat('1', 31);

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => $longPhone,

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'phone',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_invalid_customer_email(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Email Validation Product',
            'slug' => 'email-validation-product',
            'description' => 'Produk untuk pengujian validasi email.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-invalid-email',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => 'bukan-email',
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'email',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_customer_email_longer_than_100_characters(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Email Length Product',
            'slug' => 'email-length-product',
            'description' => 'Produk untuk pengujian panjang email.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-long-email',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $longEmail = str_repeat('a', 90) . '@example.com';

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => $longEmail,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'email',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_missing_shipping_address(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Shipping Address Product',
            'slug' => 'shipping-address-product',
            'description' => 'Produk untuk pengujian shipping address.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-missing-shipping-address',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                // shipping_address sengaja tidak dikirim

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_shipping_address_that_is_not_an_array(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Shipping Address Type Product',
            'slug' => 'shipping-address-type-product',
            'description' => 'Produk untuk pengujian tipe shipping address.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-address-type',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => 'Alamat Test',

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_missing_shipping_recipient_name(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Recipient Name Product',
            'slug' => 'recipient-name-product',
            'description' => 'Produk untuk pengujian recipient name.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-missing-recipient-name',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    // recipient_name sengaja tidak dikirim
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.recipient_name',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_shipping_recipient_name_that_is_not_a_string(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Recipient Name Type Product',
            'slug' => 'recipient-name-type-product',
            'description' => 'Produk untuk pengujian tipe recipient name.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-recipient-name-type',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 12345,
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.recipient_name',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_shipping_recipient_name_longer_than_100_characters(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Recipient Name Length Product',
            'slug' => 'recipient-name-length-product',
            'description' => 'Produk untuk pengujian panjang recipient name.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-recipient-name-length',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $longRecipientName = str_repeat('A', 101);

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => $longRecipientName,
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.recipient_name',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_missing_shipping_phone(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Shipping Phone Product',
            'slug' => 'shipping-phone-product',
            'description' => 'Produk untuk pengujian shipping phone.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-missing-shipping-phone',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',

                    // phone sengaja tidak dikirim

                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.phone',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_shipping_phone_that_is_not_a_string(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Shipping Phone Type Product',
            'slug' => 'shipping-phone-type-product',
            'description' => 'Produk untuk pengujian tipe shipping phone.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-phone-type',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => 81234567890,
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.phone',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_shipping_phone_longer_than_30_characters(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Shipping Phone Length Product',
            'slug' => 'shipping-phone-length-product',
            'description' => 'Produk untuk pengujian panjang shipping phone.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-phone-length',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => str_repeat('8', 31),
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.phone',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_missing_shipping_regency_id(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Missing Regency Product',
            'slug' => 'missing-regency-product',
            'description' => 'Produk untuk pengujian regency id.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-missing-regency-id',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.regency_id',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_shipping_regency_id_that_is_not_a_string(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Regency Type Product',
            'slug' => 'regency-type-product',
            'description' => 'Produk untuk pengujian tipe regency id.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-regency-type',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => 1102,
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.regency_id',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_nonexistent_shipping_regency_id(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Nonexistent Regency Product',
            'slug' => 'nonexistent-regency-product',
            'description' => 'Produk untuk pengujian regency id yang tidak ada.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-nonexistent-regency',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '9999',
                    'address' => 'Alamat Test',
                    'area' => null,
                    'city' => 'Kota Tidak Ada',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.regency_id',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_missing_shipping_address_detail(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Missing Address Product',
            'slug' => 'missing-address-product',
            'description' => 'Produk untuk pengujian alamat pengiriman.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-missing-shipping-address-detail',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.address',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_shipping_address_detail_that_is_not_a_string(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Address Type Product',
            'slug' => 'address-type-product',
            'description' => 'Produk untuk pengujian tipe alamat pengiriman.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-address-type',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => [
                        'Jalan Test',
                        'Nomor 123',
                    ],
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.address',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_shipping_address_detail_longer_than_500_characters(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Long Address Product',
            'slug' => 'long-address-product',
            'description' => 'Produk untuk pengujian panjang alamat pengiriman.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-long-shipping-address',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => str_repeat('A', 501),
                    'area' => null,
                    'city' => 'Kutacane',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.address',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_missing_shipping_area(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Missing Area Product',
            'slug' => 'missing-area-product',
            'description' => 'Produk untuk pengujian area kecamatan wajib.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-missing-shipping-area',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'city' => 'Kabupaten Aceh Tenggara',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.area',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_shipping_area_that_is_not_a_string(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Area Type Product',
            'slug' => 'area-type-product',
            'description' => 'Produk untuk pengujian tipe area kecamatan.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-area-type',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => [
                        'Kutacane',
                    ],
                    'city' => 'Kabupaten Aceh Tenggara',
                    'province' => 'Aceh',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.area',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_resolves_city_and_province_from_regency_id_without_frontend_city_and_province(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Regency Resolution Product',
            'slug' => 'regency-resolution-product',
            'description' => 'Produk untuk pengujian otomatisasi city dan province.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-regency-resolution',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldReceive('createTransaction')
                ->once()
                ->andReturn([
                    'transaction_id' => null,
                    'order_id' => 'TEST-MIDTRANS-ORDER',
                    'snap_token' => 'TEST-SNAP-TOKEN',
                    'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-SNAP-TOKEN',
                    'expiry_time' => now()->addHours(24),
                    'payload' => [],
                ]);
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',

                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],

                'courier' => 'jnt_cargo',

                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(201);

        $response->assertJsonPath(
            'data.shipping_address.city',
            'Kabupaten Aceh Tenggara'
        );

        $response->assertJsonPath(
            'data.shipping_address.province',
            'Aceh'
        );

        $response->assertJsonMissingPath(
            'data.shipping_address.regency_id'
        );
    }

    public function test_checkout_rejects_missing_shipping_postal_code(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Missing Postal Code Product',
            'slug' => 'missing-postal-code-product',
            'description' => 'Produk untuk pengujian postal code wajib.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-missing-postal-code',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',
                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => 'Kutacane',
                ],
                'courier' => 'jnt_cargo',
                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.postal_code',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_shipping_postal_code_that_is_not_a_string(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Invalid Postal Code Product',
            'slug' => 'invalid-postal-code-product',
            'description' => 'Produk untuk pengujian tipe postal code.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-invalid-postal-code',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',
                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => 'Kutacane',
                    'postal_code' => 24651,
                ],
                'courier' => 'jnt_cargo',
                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.postal_code',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_shipping_postal_code_longer_than_10_characters(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Long Postal Code Product',
            'slug' => 'long-postal-code-product',
            'description' => 'Produk untuk pengujian batas panjang postal code.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-long-postal-code',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',
                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => 'Kutacane',
                    'postal_code' => '12345678901',
                ],
                'courier' => 'jnt_cargo',
                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.postal_code',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_missing_courier(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Missing Courier Product',
            'slug' => 'missing-courier-product',
            'description' => 'Produk untuk pengujian courier wajib.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-missing-courier',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',
                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'courier',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_courier_that_is_not_a_string(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Invalid Courier Product',
            'slug' => 'invalid-courier-product',
            'description' => 'Produk untuk pengujian tipe courier.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-invalid-courier',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',
                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
                'courier' => ['jnt_cargo'],
                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'courier',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_courier_longer_than_50_characters(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Long Courier Product',
            'slug' => 'long-courier-product',
            'description' => 'Produk untuk pengujian batas panjang courier.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-long-courier',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',
                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
                'courier' => str_repeat('a', 51),
                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'courier',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_selected_item_ids_that_is_not_an_array(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Invalid Selected Items Product',
            'slug' => 'invalid-selected-items-product',
            'description' => 'Produk untuk pengujian selected item ids.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-invalid-selected-items',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',
                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
                'courier' => 'jnt_cargo',
                'selected_item_ids' => 'bukan-array',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'selected_item_ids',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_voucher_code_that_is_not_a_string(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Invalid Voucher Code Product',
            'slug' => 'invalid-voucher-code-product',
            'description' => 'Produk untuk pengujian tipe voucher code.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-invalid-voucher-code',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',
                'voucher_code' => ['VOUCHER10'],
                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
                'courier' => 'jnt_cargo',
                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'voucher_code',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_checkout_rejects_voucher_code_longer_than_100_characters(): void
    {
        DB::table('provinces')->insert([
            'id' => '11',
            'name' => 'Aceh',
            'capital' => 'Banda Aceh',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        DB::table('regencies')->insert([
            'id' => '1102',
            'province_id' => '11',
            'name' => 'Kabupaten Aceh Tenggara',
            'capital' => 'Kutacane',
            'latitude' => null,
            'longitude' => null,
            'elevation' => 0,
            'timezone' => 7,
            'area' => null,
            'population' => null,
        ]);

        $courier = ShippingCourier::create([
            'name' => 'J&T Cargo',
            'code' => 'jnt_cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'regency_id' => '1102',
            'courier_id' => $courier->id,
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Long Voucher Code Product',
            'slug' => 'long-voucher-code-product',
            'description' => 'Produk untuk pengujian batas panjang voucher code.',
            'original_price' => 800000,
            'discount_price' => null,
            'ready_stock' => 10,
            'is_active' => true,
        ]);

        ProductSpecification::create([
            'product_id' => $product->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Existing Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-long-voucher-code',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('createTransaction');
        });

        $response = $this->call(
            'POST',
            '/api/checkout',
            [],
            [
                config('customer.guest_cookie_name') =>
                    $customer->guest_token,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'name' => 'Existing Customer',
                'email' => null,
                'phone' => '081234567890',
                'voucher_code' => str_repeat('A', 101),
                'shipping_address' => [
                    'recipient_name' => 'Existing Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Alamat Test',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
                'courier' => 'jnt_cargo',
                'selected_item_ids' => [
                    $cartItem->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'voucher_code',
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }
}