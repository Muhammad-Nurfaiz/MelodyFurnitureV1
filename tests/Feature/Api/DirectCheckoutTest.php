<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\ProductSpecification;
use App\Models\ShippingCourier;
use App\Models\ShippingRate;
use App\Services\Payment\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DirectCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_direct_checkout_without_email(): void
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
            'name' => 'Test Category Direct',
            'slug' => 'test-category-direct',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Direct Checkout Product',
            'slug' => 'test-direct-checkout-product',
            'description' => 'Product khusus untuk Feature Test direct checkout.',
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
        | Guest Customer
        |--------------------------------------------------------------------------
        */

        $guestToken = 'test-guest-token-direct-checkout';

        $cartCustomer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Existing Cart
        |--------------------------------------------------------------------------
        |
        | Direct checkout seharusnya tidak menghapus cart.
        |
        */

        $cart = Cart::create([
            'customer_id' => $cartCustomer->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
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
                        'snap_token' => 'TEST-DIRECT-SNAP-TOKEN',
                        'redirect_url' =>
                            'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-DIRECT-SNAP-TOKEN',
                        'expiry_time' => $order->payment_expired_at,
                        'payload' => [],
                    ];
                });
        });

        /*
        |--------------------------------------------------------------------------
        | Direct Checkout
        |--------------------------------------------------------------------------
        */

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],

                'name' => 'Direct Customer',

                // Email sengaja tidak dikirim.

                'phone' => '081234567890',

                'courier' => 'jnt_cargo',

                'shipping_address' => [
                    'recipient_name' => 'Direct Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Direct Test No. 1',
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
        | Response Shipping Address
        |--------------------------------------------------------------------------
        */

        $response->assertJsonPath(
            'data.shipping_address.city',
            'Kabupaten Aceh Tenggara'
        );

        $response->assertJsonPath(
            'data.shipping_address.province',
            'Aceh'
        );

        $response->assertJsonPath(
            'data.shipping_address.area',
            'Kutacane'
        );

        $response->assertJsonPath(
            'data.shipping_address.postal_code',
            '24676'
        );

        $response->assertJsonMissingPath(
            'data.shipping_address.regency_id'
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
            'Direct Customer',
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
            'TEST-DIRECT-SNAP-TOKEN',
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
        |
        | Cart harus tetap utuh karena ini direct checkout.
        |
        */

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseCount(
            'cart_items',
            1
        );
    }

    public function test_direct_checkout_requires_regency_id(): void
    {
        $category = Category::create([
            'name' => 'Test Category Direct Validation',
            'slug' => 'test-category-direct-validation',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Direct Validation Product',
            'slug' => 'test-direct-validation-product',
            'description' => 'Product untuk validasi direct checkout.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'origin_city' => 'Malang',
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        $guestToken = 'test-guest-token-direct-validation';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Direct Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Direct Customer',
                    'phone' => '081234567890',

                    // regency_id sengaja tidak dikirim.

                    'address' => 'Jl. Direct Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.regency_id',
        ]);
    }

    public function test_direct_checkout_rejects_invalid_regency_id(): void
    {
        $category = Category::create([
            'name' => 'Test Category Invalid Regency',
            'slug' => 'test-category-invalid-regency',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Invalid Regency Product',
            'slug' => 'test-invalid-regency-product',
            'description' => 'Product untuk validasi regency direct checkout.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        $guestToken = 'test-guest-token-invalid-regency';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Direct Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Direct Customer',
                    'phone' => '081234567890',

                    // Regency ID sengaja tidak ada di database.
                    'regency_id' => '9999',

                    'address' => 'Jl. Direct Test No. 1',
                    'area' => 'Kecamatan Test',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'shipping_address.regency_id',
        ]);
    }

    public function test_direct_checkout_requires_positive_quantity(): void
    {
        $category = Category::create([
            'name' => 'Test Category Quantity',
            'slug' => 'test-category-quantity',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Quantity Product',
            'slug' => 'test-quantity-product',
            'description' => 'Product untuk validasi quantity direct checkout.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        $guestToken = 'test-guest-token-direct-quantity';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 0,
                    ],
                ],
                'name' => 'Direct Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Direct Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Direct Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'items.0.quantity',
        ]);
    }

    public function test_direct_checkout_requires_items(): void
    {
        $guestToken = 'test-guest-token-direct-items';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'name' => 'Direct Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Direct Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Direct Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'items',
        ]);
    }

    public function test_direct_checkout_requires_at_least_one_item(): void
    {
        $guestToken = 'test-guest-token-direct-empty-items';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [],
                'name' => 'Direct Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Direct Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Direct Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'items',
        ]);
    }

    public function test_direct_checkout_requires_product_id(): void
    {
        $guestToken = 'test-guest-token-direct-product-id';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Direct Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Direct Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Direct Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'items.0.product_id',
        ]);
    }

    public function test_direct_checkout_requires_product_id_uuid(): void
    {
        $guestToken = 'test-guest-token-direct-product-uuid';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => 'not-a-uuid',
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Direct Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Direct Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Direct Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'items.0.product_id',
        ]);
    }

    public function test_direct_checkout_rejects_nonexistent_product_id(): void
    {
        $guestToken = 'test-guest-token-direct-product-exists';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' =>
                            '00000000-0000-0000-0000-000000000000',
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Direct Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Direct Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Direct Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'items.0.product_id',
        ]);
    }

    public function test_direct_checkout_requires_integer_quantity(): void
    {
        $category = Category::create([
            'name' => 'Test Category Quantity Integer',
            'slug' => 'test-category-quantity-integer',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Integer Quantity Product',
            'slug' => 'test-integer-quantity-product',
            'description' => 'Product untuk validasi integer quantity.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        $guestToken = 'test-guest-token-direct-quantity-integer';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 'satu',
                    ],
                ],
                'name' => 'Direct Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Direct Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Direct Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'items.0.quantity',
        ]);
    }

    public function test_direct_checkout_rejects_quantity_above_ready_stock(): void
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
            'name' => 'Test Category Stock Limit',
            'slug' => 'test-category-stock-limit',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Stock Limit Product',
            'slug' => 'test-stock-limit-product',
            'description' => 'Product untuk validasi batas stok direct checkout.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
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

        $guestToken = 'test-guest-token-direct-stock-limit';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 11,
                    ],
                ],
                'name' => 'Direct Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Direct Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Direct Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' =>
                'Stok produk Test Stock Limit Product tidak mencukupi. Tersedia: 10.',
        ]);

        $this->assertDatabaseMissing('orders', [
            'customer_id' => null,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 10,
        ]);
    }

    public function test_direct_checkout_rejects_out_of_stock_product(): void
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

        $category = Category::create([
            'name' => 'Test Category Out Of Stock',
            'slug' => 'test-category-out-of-stock',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Out Of Stock Product',
            'slug' => 'test-out-of-stock-product',
            'description' => 'Product yang sedang habis untuk direct checkout.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 0,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        $guestToken = 'test-guest-token-direct-out-of-stock';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Direct Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Direct Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Direct Test No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' =>
                'Produk Test Out Of Stock Product sedang habis.',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 0,
        ]);
    }

    public function test_customer_can_direct_checkout_multiple_products(): void
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
            'name' => 'Test Category Multiple Direct',
            'slug' => 'test-category-multiple-direct',
        ]);

        $productOne = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Direct Product One',
            'slug' => 'test-direct-product-one',
            'description' => 'Produk pertama.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 20,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $productOne->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $productTwo = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Direct Product Two',
            'slug' => 'test-direct-product-two',
            'description' => 'Produk kedua.',
            'product_detail' => null,
            'original_price' => 500000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 15,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $productTwo->id,
            'dimensions' => '80 x 40 x 40 cm',
            'weight' => 5,
            'packing_weight' => 6,
            'load_capacity' => '30 kg',
            'assembly_required' => false,
        ]);

        $guestToken = 'test-guest-token-direct-multiple-products';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->once()
                ->andReturnUsing(function (Order $order) {
                    return [
                        'transaction_id' => null,
                        'order_id' => $order->midtrans_order_id,
                        'snap_token' => 'TEST-MULTIPLE-DIRECT-SNAP-TOKEN',
                        'redirect_url' =>
                            'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-MULTIPLE-DIRECT-SNAP-TOKEN',
                        'expiry_time' => $order->payment_expired_at,
                        'payload' => [],
                    ];
                });
        });

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $productOne->id,
                        'quantity' => 1,
                    ],
                    [
                        'product_id' => $productTwo->id,
                        'quantity' => 2,
                    ],
                ],
                'name' => 'Direct Multiple Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Direct Multiple Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Direct Multiple No. 1',
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

        $orderId = $response->json('data.id');

        $this->assertDatabaseCount('order_items', 2);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $productOne->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $productTwo->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $productOne->id,
            'ready_stock' => 19,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $productTwo->id,
            'ready_stock' => 13,
        ]);
    }

    public function test_direct_checkout_allows_quantity_equal_to_ready_stock(): void
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
            'name' => 'Test Category Exact Stock',
            'slug' => 'test-category-exact-stock',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Exact Stock Product',
            'slug' => 'test-exact-stock-product',
            'description' => 'Product untuk test pembelian seluruh stok.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
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

        $guestToken = 'test-guest-token-direct-exact-stock';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock
                ->shouldReceive('createTransaction')
                ->once()
                ->andReturnUsing(function (Order $order) {
                    return [
                        'transaction_id' => null,
                        'order_id' => $order->midtrans_order_id,
                        'snap_token' => 'TEST-EXACT-STOCK-SNAP-TOKEN',
                        'redirect_url' =>
                            'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-EXACT-STOCK-SNAP-TOKEN',
                        'expiry_time' => $order->payment_expired_at,
                        'payload' => [],
                    ];
                });
        });

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 10,
                    ],
                ],
                'name' => 'Exact Stock Customer',
                'phone' => '081234567890',
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Exact Stock Customer',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Exact Stock No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24676',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertCreated();

        $orderId = $response->json('data.id');

        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 0,
        ]);
    }

    public function test_customer_can_direct_checkout_with_valid_fixed_voucher(): void
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
            'name' => 'Test Category Voucher',
            'slug' => 'test-category-voucher',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Direct Voucher Product',
            'slug' => 'test-direct-voucher-product',
            'description' => 'Product untuk test direct checkout dengan voucher.',
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
        | Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = Voucher::create([
            'code' => 'DIRECT100K',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Guest Customer
        |--------------------------------------------------------------------------
        */

        $guestToken = 'test-guest-token-direct-voucher';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
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
                        'snap_token' => 'TEST-DIRECT-VOUCHER-SNAP-TOKEN',
                        'redirect_url' =>
                            'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-DIRECT-VOUCHER-SNAP-TOKEN',
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
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Customer Voucher',
                'phone' => '081234567890',
                'voucher_code' => $voucher->code,
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Customer Voucher',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Test Voucher No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Checkout berhasil.')
            ->assertJsonPath('data.voucher_discount', '100000.00');
        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */

        $order = Order::latest('created_at')->first();

        $this->assertNotNull($order);

        $this->assertSame(
            1203000.0,
            (float) $order->total_product_price
        );

        $this->assertSame(
            100000.0,
            (float) $order->voucher_discount_amount
        );

        $this->assertSame(
            414400.0,
            (float) $order->shipping_fee
        );

        $this->assertSame(
            1517400.0,
            (float) $order->total_payment
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
    }

    public function test_customer_can_direct_checkout_with_valid_percentage_voucher(): void
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
            'name' => 'Test Category Percentage Voucher',
            'slug' => 'test-category-percentage-voucher',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Percentage Voucher Product',
            'slug' => 'test-percentage-voucher-product',
            'description' => 'Product untuk test percentage voucher.',
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
        | Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = Voucher::create([
            'code' => 'DIRECT10PERCENT',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Guest Customer
        |--------------------------------------------------------------------------
        */

        $guestToken = 'test-guest-token-direct-percentage-voucher';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
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
                        'snap_token' => 'TEST-DIRECT-PERCENTAGE-SNAP-TOKEN',
                        'redirect_url' =>
                            'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-DIRECT-PERCENTAGE-SNAP-TOKEN',
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
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Customer Percentage Voucher',
                'phone' => '081234567890',
                'voucher_code' => $voucher->code,
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Customer Percentage Voucher',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Test Percentage No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Checkout berhasil.')
            ->assertJsonPath('data.voucher_discount', '120300.00');

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */

        $order = Order::latest('created_at')->first();

        $this->assertNotNull($order);

        $this->assertSame(
            1203000.0,
            (float) $order->total_product_price
        );

        $this->assertSame(
            120300.0,
            (float) $order->voucher_discount_amount
        );

        $this->assertSame(
            414400.0,
            (float) $order->shipping_fee
        );

        $this->assertSame(
            1497100.0,
            (float) $order->total_payment
        );
    }

    public function test_customer_can_direct_checkout_with_percentage_voucher_max_discount(): void
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
            'name' => 'Test Category Max Discount',
            'slug' => 'test-category-max-discount',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Max Discount Product',
            'slug' => 'test-max-discount-product',
            'description' => 'Product untuk test batas maksimal diskon voucher.',
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
        | Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = Voucher::create([
            'code' => 'MAX200K',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'min_order_amount' => 0,
            'max_discount_amount' => 200000,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Guest Customer
        |--------------------------------------------------------------------------
        */

        $guestToken = 'test-guest-token-direct-max-discount';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
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
                        'snap_token' => 'TEST-DIRECT-MAX-DISCOUNT-SNAP-TOKEN',
                        'redirect_url' =>
                            'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-DIRECT-MAX-DISCOUNT-SNAP-TOKEN',
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
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Customer Max Discount',
                'phone' => '081234567890',
                'voucher_code' => $voucher->code,
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Customer Max Discount',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Test Max Discount No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Checkout berhasil.')
            ->assertJsonPath('data.voucher_discount', '200000.00');

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */

        $order = Order::latest('created_at')->first();

        $this->assertNotNull($order);

        $this->assertSame(
            1203000.0,
            (float) $order->total_product_price
        );

        $this->assertSame(
            200000.0,
            (float) $order->voucher_discount_amount
        );

        $this->assertSame(
            414400.0,
            (float) $order->shipping_fee
        );

        $this->assertSame(
            1417400.0,
            (float) $order->total_payment
        );
    }

    public function test_direct_checkout_caps_fixed_voucher_discount_at_90_percent(): void
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
            'name' => 'Test Category Voucher Cap',
            'slug' => 'test-category-voucher-cap',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Voucher Cap Product',
            'slug' => 'test-voucher-cap-product',
            'description' => 'Product untuk test batas 90 persen voucher.',
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
        | Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = Voucher::create([
            'code' => 'OVER2000000',
            'discount_type' => 'fixed',
            'discount_value' => 2000000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Guest Customer
        |--------------------------------------------------------------------------
        */

        $guestToken = 'test-guest-token-direct-voucher-cap';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
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
                        'snap_token' => 'TEST-DIRECT-VOUCHER-CAP-SNAP-TOKEN',
                        'redirect_url' =>
                            'https://app.sandbox.midtrans.com/snap/v2/vtweb/TEST-DIRECT-VOUCHER-CAP-SNAP-TOKEN',
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
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Customer Voucher Cap',
                'phone' => '081234567890',
                'voucher_code' => $voucher->code,
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Customer Voucher Cap',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Test Voucher Cap No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Checkout berhasil.')
            ->assertJsonPath('data.voucher_discount', '1082700.00')
            ->assertJsonPath('data.total_payment', '534700.00');

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */

        $order = Order::latest('created_at')->first();

        $this->assertNotNull($order);

        $this->assertSame(
            1203000.0,
            (float) $order->total_product_price
        );

        $this->assertSame(
            1082700.0,
            (float) $order->voucher_discount_amount
        );

        $this->assertSame(
            414400.0,
            (float) $order->shipping_fee
        );

        $this->assertSame(
            534700.0,
            (float) $order->total_payment
        );
    }

    public function test_direct_checkout_rejects_inactive_voucher(): void
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
        | Product
        |--------------------------------------------------------------------------
        */

        $category = Category::create([
            'name' => 'Test Category Inactive Voucher',
            'slug' => 'test-category-inactive-voucher',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Inactive Voucher Product',
            'slug' => 'test-inactive-voucher-product',
            'description' => 'Product untuk test voucher tidak aktif.',
            'product_detail' => null,
            'original_price' => 1515000,
            'discount_price' => 1203000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 100,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
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
        | Inactive Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = Voucher::create([
            'code' => 'INACTIVEVOUCHER',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => false,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Guest Customer
        |--------------------------------------------------------------------------
        */

        $guestToken = 'test-guest-token-direct-inactive-voucher';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Checkout
        |--------------------------------------------------------------------------
        */

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Customer Inactive Voucher',
                'phone' => '081234567890',
                'voucher_code' => $voucher->code,
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Customer Inactive Voucher',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Test Inactive Voucher No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        $response
            ->assertStatus(422)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'message',
                'Validation failed.'
            )
            ->assertJsonPath(
                'errors.voucher.0',
                'Voucher tidak aktif.'
            );
    }

    public function test_direct_checkout_rejects_voucher_before_start_date(): void
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
        | Product
        |--------------------------------------------------------------------------
        */

        $category = Category::create([
            'name' => 'Test Category Voucher Start',
            'slug' => 'test-category-voucher-start',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Voucher Start Product',
            'slug' => 'test-voucher-start-product',
            'description' => 'Product untuk test voucher belum mulai.',
            'product_detail' => null,
            'original_price' => 1515000,
            'discount_price' => 1203000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 100,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
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
        | Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = Voucher::create([
            'code' => 'NOTSTARTED',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->addDay(),
            'expiry_date' => now()->addDays(3),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Guest Customer
        |--------------------------------------------------------------------------
        */

        $guestToken = 'test-guest-token-direct-voucher-start';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Checkout
        |--------------------------------------------------------------------------
        */

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Customer Voucher Start',
                'phone' => '081234567890',
                'voucher_code' => $voucher->code,
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Customer Voucher Start',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Test Voucher Start No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        $response
            ->assertStatus(422)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'message',
                'Validation failed.'
            )
            ->assertJsonPath(
                'errors.voucher.0',
                'Voucher belum mulai berlaku.'
            );
    }

    public function test_direct_checkout_rejects_expired_voucher(): void
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
        | Product
        |--------------------------------------------------------------------------
        */

        $category = Category::create([
            'name' => 'Test Category Expired Voucher',
            'slug' => 'test-category-expired-voucher',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Expired Voucher Product',
            'slug' => 'test-expired-voucher-product',
            'description' => 'Product untuk test voucher expired.',
            'product_detail' => null,
            'original_price' => 1515000,
            'discount_price' => 1203000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 100,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
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
        | Expired Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = Voucher::create([
            'code' => 'EXPIREDVOUCHER',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDays(3),
            'expiry_date' => now()->subDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Guest Customer
        |--------------------------------------------------------------------------
        */

        $guestToken = 'test-guest-token-direct-expired-voucher';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Checkout
        |--------------------------------------------------------------------------
        */

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Customer Expired Voucher',
                'phone' => '081234567890',
                'voucher_code' => $voucher->code,
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Customer Expired Voucher',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Test Expired Voucher No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        $response
            ->assertStatus(422)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'message',
                'Validation failed.'
            )
            ->assertJsonPath(
                'errors.voucher.0',
                'Voucher sudah expired.'
            );
    }

    public function test_direct_checkout_rejects_voucher_below_minimum_order_amount(): void
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
        | Product
        |--------------------------------------------------------------------------
        */

        $category = Category::create([
            'name' => 'Test Category Voucher Minimum',
            'slug' => 'test-category-voucher-minimum',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Voucher Minimum Product',
            'slug' => 'test-voucher-minimum-product',
            'description' => 'Product untuk test minimum pembelian voucher.',
            'product_detail' => null,
            'original_price' => 1515000,
            'discount_price' => 1203000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 100,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
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
        | Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = Voucher::create([
            'code' => 'MINORDER2M',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'min_order_amount' => 2000000,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Guest Customer
        |--------------------------------------------------------------------------
        */

        $guestToken = 'test-guest-token-direct-voucher-minimum';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Checkout
        |--------------------------------------------------------------------------
        */

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Customer Voucher Minimum',
                'phone' => '081234567890',
                'voucher_code' => $voucher->code,
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Customer Voucher Minimum',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Test Voucher Minimum No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        $response
            ->assertStatus(422)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'message',
                'Validation failed.'
            )
            ->assertJsonPath(
                'errors.voucher.0',
                'Minimal pembelian belum memenuhi.'
            );
    }

    public function test_direct_checkout_rejects_voucher_when_usage_limit_reached(): void
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
        | Product
        |--------------------------------------------------------------------------
        */

        $category = Category::create([
            'name' => 'Test Category Voucher Limit',
            'slug' => 'test-category-voucher-limit',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Voucher Limit Product',
            'slug' => 'test-voucher-limit-product',
            'description' => 'Product untuk test usage limit voucher.',
            'product_detail' => null,
            'original_price' => 1515000,
            'discount_price' => 1203000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 100,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
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
        | Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = Voucher::create([
            'code' => 'LIMITREACHED',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => 5,
            'used_count' => 5,
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Guest Customer
        |--------------------------------------------------------------------------
        */

        $guestToken = 'test-guest-token-direct-voucher-limit';

        Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => $guestToken,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Checkout
        |--------------------------------------------------------------------------
        */

        $response = $this->call(
            'POST',
            '/api/checkout/direct',
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'name' => 'Customer Voucher Limit',
                'phone' => '081234567890',
                'voucher_code' => $voucher->code,
                'courier' => 'jnt_cargo',
                'shipping_address' => [
                    'recipient_name' => 'Customer Voucher Limit',
                    'phone' => '081234567890',
                    'regency_id' => '1102',
                    'address' => 'Jl. Test Voucher Limit No. 1',
                    'area' => 'Kutacane',
                    'postal_code' => '24651',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        $response
            ->assertStatus(422)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'message',
                'Validation failed.'
            )
            ->assertJsonPath(
                'errors.voucher.0',
                'Batas penggunaan voucher sudah tercapai.'
            );
    }

}