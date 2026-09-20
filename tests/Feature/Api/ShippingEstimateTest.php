<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\ShippingCourier;
use App\Models\ShippingRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShippingEstimateTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_get_shipping_estimate_from_cart(): void
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
        | Courier & Rate
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
            'name' => 'Shipping Test Category',
            'slug' => 'shipping-test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Test Product',
            'slug' => 'shipping-test-product',
            'description' => 'Product khusus untuk Feature Test shipping estimate.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
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
        | Customer & Cart
        |--------------------------------------------------------------------------
        */

        $customer = Customer::create([
            'name' => 'Shipping Test Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-estimate-test',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Request
        |--------------------------------------------------------------------------
        */

        $response = $this->call(
            'POST',
            '/api/shipping/estimate',
            [],
            [
                config('customer.guest_cookie_name') => 'guest-token-shipping-estimate-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
                'courier' => 'jnt_cargo',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertOk();

        $response->assertJson([
            'message' => 'Ongkir berhasil dihitung.',
            'data' => [
                'weight' => 35,
                'fee' => 414400,
                'regency_id' => '1102',
                'courier' => 'jnt_cargo',
                'service' => 'regular',
            ],
        ]);
    }

    public function test_customer_cannot_get_shipping_estimate_when_cart_is_empty(): void
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

        $customer = Customer::create([
            'name' => 'Shipping Estimate Empty Cart',
            'phone' => '081234567891',
            'email' => 'shipping-empty-cart@test.com',
            'address_detail' => 'Jl. Test No. 2',
            'guest_token' => 'guest-token-shipping-empty-cart-test',
        ]);

        $response = $this->call(
            'POST',
            '/api/shipping/estimate',
            [],
            [
                config('customer.guest_cookie_name')
                    => 'guest-token-shipping-empty-cart-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
                'courier' => 'jnt_cargo',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Cart kosong.',
            'errors' => null,
        ]);
    }

    public function test_customer_cannot_get_shipping_estimate_when_product_has_no_specification(): void
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
            'name' => 'Shipping No Specification Category',
            'slug' => 'shipping-no-specification-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Product Without Specification',
            'slug' => 'product-without-specification',
            'description' => 'Produk untuk test shipping tanpa spesifikasi.',
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

        // Sengaja TIDAK membuat ProductSpecification.

        $customer = Customer::create([
            'name' => 'Shipping No Specification',
            'email' => null,
            'phone' => '081234567892',
            'address_detail' => 'Jl. Test No. 3',
            'guest_token' => 'guest-token-shipping-no-specification-test',
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
            '/api/shipping/estimate',
            [],
            [
                config('customer.guest_cookie_name')
                    => 'guest-token-shipping-no-specification-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
                'courier' => 'jnt_cargo',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Produk Product Without Specification belum memiliki spesifikasi.',
            'errors' => null,
        ]);
    }

    public function test_customer_gets_shipping_estimate_based_on_cart_quantity(): void
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
            'name' => 'Shipping Quantity Category',
            'slug' => 'shipping-quantity-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Quantity Product',
            'slug' => 'shipping-quantity-product',
            'description' => 'Produk untuk pengujian quantity shipping.',
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
            'dimensions' => '159 x 39.6 x 48 cm',
            'weight' => 32.50,
            'packing_weight' => 34.50,
            'load_capacity' => '100 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Shipping Quantity Customer',
            'email' => null,
            'phone' => '081234567893',
            'address_detail' => 'Jl. Test No. 4',
            'guest_token' => 'guest-token-shipping-quantity-test',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->call(
            'POST',
            '/api/shipping/estimate',
            [],
            [
                config('customer.guest_cookie_name')
                    => 'guest-token-shipping-quantity-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
                'courier' => 'jnt_cargo',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Ongkir berhasil dihitung.',
            'data' => [
                'weight' => 69,
                'fee' => 816960,
                'regency_id' => '1102',
                'courier' => 'jnt_cargo',
                'service' => 'regular',
            ],
        ]);
    }

    public function test_shipping_estimate_requires_regency_id(): void
    {
        $customer = Customer::create([
            'name' => 'Shipping Missing Regency',
            'email' => null,
            'phone' => '081234567894',
            'address_detail' => 'Jl. Test No. 5',
            'guest_token' => 'guest-token-shipping-missing-regency-test',
        ]);

        $response = $this->call(
            'POST',
            '/api/shipping/estimate',
            [],
            [
                config('customer.guest_cookie_name')
                    => 'guest-token-shipping-missing-regency-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'courier' => 'jnt_cargo',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'regency_id',
        ]);
    }

    public function test_shipping_estimate_rejects_invalid_regency_id(): void
    {
        $customer = Customer::create([
            'name' => 'Shipping Invalid Regency',
            'email' => null,
            'phone' => '081234567895',
            'address_detail' => 'Jl. Test No. 6',
            'guest_token' => 'guest-token-shipping-invalid-regency-test',
        ]);

        $response = $this->call(
            'POST',
            '/api/shipping/estimate',
            [],
            [
                config('customer.guest_cookie_name')
                    => 'guest-token-shipping-invalid-regency-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '9999',
                'courier' => 'jnt_cargo',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'regency_id',
        ]);
    }

    public function test_shipping_estimate_requires_courier(): void
    {
        $customer = Customer::create([
            'name' => 'Shipping Missing Courier',
            'email' => null,
            'phone' => '081234567896',
            'address_detail' => 'Jl. Test No. 7',
            'guest_token' => 'guest-token-shipping-missing-courier-test',
        ]);

        $response = $this->call(
            'POST',
            '/api/shipping/estimate',
            [],
            [
                config('customer.guest_cookie_name')
                    => 'guest-token-shipping-missing-courier-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'courier',
        ]);
    }

    public function test_shipping_estimate_rejects_non_string_courier(): void
    {
        $customer = Customer::create([
            'name' => 'Shipping Invalid Courier',
            'email' => null,
            'phone' => '081234567897',
            'address_detail' => 'Jl. Test No. 8',
            'guest_token' => 'guest-token-shipping-invalid-courier-test',
        ]);

        $response = $this->call(
            'POST',
            '/api/shipping/estimate',
            [],
            [
                config('customer.guest_cookie_name')
                    => 'guest-token-shipping-invalid-courier-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
                'courier' => 12345,
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'courier',
        ]);
    }

    public function test_shipping_estimate_rejects_courier_over_max_length(): void
    {
        $customer = Customer::create([
            'name' => 'Shipping Long Courier',
            'email' => null,
            'phone' => '081234567898',
            'address_detail' => 'Jl. Test No. 9',
            'guest_token' => 'guest-token-shipping-long-courier-test',
        ]);

        $response = $this->call(
            'POST',
            '/api/shipping/estimate',
            [],
            [
                config('customer.guest_cookie_name')
                    => 'guest-token-shipping-long-courier-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
                'courier' => str_repeat('a', 51),
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'courier',
        ]);
    }

    public function test_shipping_estimate_rejects_inactive_shipping_rate(): void
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

        $customer = Customer::create([
            'name' => 'Shipping Courier Without Rate',
            'email' => null,
            'phone' => '081234567899',
            'address_detail' => 'Jl. Test No. 10',
            'guest_token' => 'guest-token-shipping-courier-without-rate-test',
        ]);

        $category = Category::create([
            'name' => 'Test Shipping Category',
            'slug' => 'test-shipping-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Shipping Product',
            'slug' => 'test-shipping-product',
            'description' => 'Product khusus untuk Feature Test shipping.',
            'product_detail' => null,
            'original_price' => 100000,
            'discount_price' => 100000,
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
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 10,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        Cart::create([
            'customer_id' => $customer->id,
        ]);

        $cart = Cart::where('customer_id', $customer->id)->first();

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'test_inactive_rate',
            'name' => 'Test Courier Without Rate',
            'is_active' => false,
        ]);

        $response = $this->call(
            'POST',
            '/api/shipping/estimate',
            [],
            [
                config('customer.guest_cookie_name')
                    => 'guest-token-shipping-courier-without-rate-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
                'courier' => $courier->code,
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' =>
                'Tarif pengiriman untuk regency 1102 dan courier test_inactive_rate tidak ditemukan.',
            'errors' => null,
        ]);
    }

    public function test_shipping_estimate_tiered_rate_at_ten_kg_uses_first_price(): void
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

        $customer = Customer::create([
            'name' => 'Shipping Tiered 10 KG',
            'email' => null,
            'phone' => '081234567901',
            'address_detail' => 'Jl. Test No. 12',
            'guest_token' => 'guest-token-shipping-tiered-10kg-test',
        ]);

        $category = Category::create([
            'name' => 'Test Shipping Category',
            'slug' => 'test-shipping-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Product Tiered 10 KG',
            'slug' => 'test-product-tiered-10-kg',
            'description' => 'Product untuk test tiered 10 kg.',
            'product_detail' => null,
            'original_price' => 100000,
            'discount_price' => 100000,
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
            'dimensions' => '10 x 10 x 10 cm',
            'weight' => 10,
            'packing_weight' => 10,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $product2 = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Product Multiple 2',
            'slug' => 'test-product-multiple-2',
            'description' => 'Product untuk test multiple 2.',
            'product_detail' => null,
            'original_price' => 200000,
            'discount_price' => 200000,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 5,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $product2->id,
            'dimensions' => '20 x 20 x 20 cm',
            'weight' => 10.25,
            'packing_weight' => 10.25,
            'load_capacity' => '100 kg',
            'assembly_required' => true,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'test_tiered',
            'name' => 'Test Courier Tiered',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'tiered',
            'price_per_kg' => null,
            'first_price' => 100000,
            'additional_price_per_kg' => 15000,
            'is_active' => true,
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
            '/api/shipping/estimate',
            [],
            [
                config('customer.guest_cookie_name')
                    => 'guest-token-shipping-tiered-10kg-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
                'courier' => 'test_tiered',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(200);

        $response->assertJsonPath('data.weight', 10);
        $response->assertJsonPath('data.fee', 100000);
        $response->assertJsonPath('data.service', 'regular');
    }

    public function test_shipping_estimate_tiered_rate_above_ten_kg_uses_additional_price(): void
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

        $customer = Customer::create([
            'name' => 'Shipping Tiered Above 10 KG',
            'email' => null,
            'phone' => '081234567902',
            'address_detail' => 'Jl. Test No. 13',
            'guest_token' => 'guest-token-shipping-tiered-above-10kg-test',
        ]);

        $category = Category::create([
            'name' => 'Test Shipping Category',
            'slug' => 'test-shipping-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Test Product Tiered 12 KG',
            'slug' => 'test-product-tiered-12-kg',
            'description' => 'Product untuk test tiered 12 kg.',
            'product_detail' => null,
            'original_price' => 100000,
            'discount_price' => 100000,
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
            'dimensions' => '10 x 10 x 10 cm',
            'weight' => 12,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $courier = ShippingCourier::create([
            'code' => 'test_tiered',
            'name' => 'Test Courier Tiered',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'tiered',
            'price_per_kg' => null,
            'first_price' => 100000,
            'additional_price_per_kg' => 15000,
            'is_active' => true,
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
            '/api/shipping/estimate',
            [],
            [
                config('customer.guest_cookie_name')
                    => 'guest-token-shipping-tiered-above-10kg-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
                'courier' => 'test_tiered',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(200);

        $response->assertJsonPath('data.weight', 12);
        $response->assertJsonPath('data.fee', 130000);
        $response->assertJsonPath('data.service', 'regular');
    }

    public function test_customer_can_get_shipping_estimates_for_all_couriers(): void
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

        $jnt = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $jnt->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $sentral = ShippingCourier::create([
            'code' => 'sentral_cargo',
            'name' => 'Sentral Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $sentral->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 10000,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Shipping All Test Category',
            'slug' => 'shipping-all-test-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping All Test Product',
            'slug' => 'shipping-all-test-product',
            'description' => 'Product khusus untuk test shipping estimate all.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
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

        $customer = Customer::create([
            'name' => 'Shipping All Test Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-test',
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
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertOk();

        $response->assertJson([
            'message' => 'Estimasi ongkir semua kurir berhasil dihitung.',
            'data' => [
                'regency_id' => '1102',
                'total_weight' => 34.50,
            ],
        ]);

        $response->assertJsonCount(2, 'data.couriers');

        $response->assertJsonFragment([
            'courier_code' => 'jnt_cargo',
            'courier_name' => 'J&T Cargo',
            'service' => 'regular',
            'weight' => 35,
            'fee' => 414400,
            'available' => true,
        ]);

        $response->assertJsonFragment([
            'courier_code' => 'sentral_cargo',
            'courier_name' => 'Sentral Cargo',
            'service' => 'regular',
            'weight' => 35,
            'fee' => 350000,
            'available' => true,
        ]);
    }

    public function test_shipping_estimate_all_marks_courier_unavailable_when_rate_is_missing(): void
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

        $jnt = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $jnt->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $sentral = ShippingCourier::create([
            'code' => 'sentral_cargo',
            'name' => 'Sentral Cargo',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Shipping Missing Rate Category',
            'slug' => 'shipping-missing-rate-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Missing Rate Product',
            'slug' => 'shipping-missing-rate-product',
            'description' => 'Product test.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
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

        $customer = Customer::create([
            'name' => 'Shipping Missing Rate Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-missing-rate-test',
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
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-missing-rate-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertOk();

        $response->assertJsonFragment([
            'courier_code' => 'jnt_cargo',
            'available' => true,
            'weight' => 35,
            'fee' => 414400,
        ]);

        $response->assertJsonFragment([
            'courier_code' => 'sentral_cargo',
            'available' => false,
            'weight' => null,
            'fee' => null,
        ]);
    }

    public function test_shipping_estimate_all_marks_courier_unavailable_when_rate_is_inactive(): void
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
            'code' => 'inactive_rate_courier',
            'name' => 'Inactive Rate Courier',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 10000,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => false,
        ]);

        $category = Category::create([
            'name' => 'Shipping Inactive Rate Category',
            'slug' => 'shipping-inactive-rate-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Inactive Rate Product',
            'slug' => 'shipping-inactive-rate-product',
            'description' => 'Product test.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
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

        $customer = Customer::create([
            'name' => 'Shipping Inactive Rate Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-inactive-rate-test',
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
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-inactive-rate-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertOk();

        $response->assertJsonFragment([
            'courier_code' => 'inactive_rate_courier',
            'courier_name' => 'Inactive Rate Courier',
            'service' => 'regular',
            'weight' => null,
            'fee' => null,
            'available' => false,
        ]);
    }

    public function test_shipping_estimate_all_requires_regency_id(): void
    {
        $customer = Customer::create([
            'name' => 'Shipping Validation Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-validation-test',
        ]);

        $response = $this->call(
            'POST',
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-validation-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'regency_id',
        ]);
    }

    public function test_shipping_estimate_all_rejects_invalid_regency_id(): void
    {
        $customer = Customer::create([
            'name' => 'Shipping Invalid Regency Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-invalid-regency-test',
        ]);

        $response = $this->call(
            'POST',
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-invalid-regency-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '9999',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'regency_id',
        ]);
    }

    public function test_shipping_estimate_all_rejects_empty_cart(): void
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

        $customer = Customer::create([
            'name' => 'Shipping Empty Cart Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-empty-cart-test',
        ]);

        Cart::create([
            'customer_id' => $customer->id,
        ]);

        $response = $this->call(
            'POST',
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-empty-cart-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Cart kosong.',
            'errors' => null,
        ]);
    }

    public function test_shipping_estimate_all_rejects_product_without_specification(): void
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
            'name' => 'Shipping No Specification Category',
            'slug' => 'shipping-no-specification-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Product Without Specification',
            'slug' => 'product-without-specification',
            'description' => 'Product test.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 100,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        $customer = Customer::create([
            'name' => 'Shipping No Specification Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-no-spec-test',
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
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-no-spec-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'Produk Product Without Specification belum memiliki spesifikasi.',
            'errors' => null,
        ]);
    }

    public function test_shipping_estimate_all_calculates_total_weight_from_cart_quantity(): void
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
            'name' => 'Shipping Quantity All Category',
            'slug' => 'shipping-quantity-all-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Quantity All Product',
            'slug' => 'shipping-quantity-all-product',
            'description' => 'Product test.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
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
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 10.25,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Shipping Quantity All Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-quantity-test',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->call(
            'POST',
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-quantity-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'regency_id' => '1102',
                'total_weight' => 20.5,
            ],
        ]);

        $response->assertJsonFragment([
            'courier_code' => 'jnt_cargo',
            'weight' => 21,
            'fee' => 248640,
            'available' => true,
        ]);
    }

    public function test_shipping_estimate_all_requires_customer_session(): void
    {
        $response = $this->call(
            'POST',
            '/api/shipping/estimate-all',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertUnauthorized();

        $response->assertJson([
            'success' => false,
            'message' => 'Guest session tidak ditemukan.',
            'errors' => null,
        ]);
    }

    public function test_shipping_estimate_all_excludes_inactive_courier(): void
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

        $activeCourier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $activeCourier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $inactiveCourier = ShippingCourier::create([
            'code' => 'inactive_cargo',
            'name' => 'Inactive Cargo',
            'is_active' => false,
        ]);

        ShippingRate::create([
            'courier_id' => $inactiveCourier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 10000,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Shipping Inactive All Category',
            'slug' => 'shipping-inactive-all-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Inactive All Product',
            'slug' => 'shipping-inactive-all-product',
            'description' => 'Product test.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
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
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 10,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Shipping Inactive All Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-inactive-test',
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
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-inactive-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertOk();

        $response->assertJsonCount(1, 'data.couriers');

        $response->assertJsonFragment([
            'courier_code' => 'jnt_cargo',
            'available' => true,
            'weight' => 10,
            'fee' => 118400,
        ]);

        $response->assertJsonMissing([
            'courier_code' => 'inactive_cargo',
        ]);
    }

    public function test_shipping_estimate_all_marks_courier_unavailable_when_rate_exists_for_different_regency(): void
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
            [
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
            ],
            [
                'id' => '1103',
                'province_id' => '11',
                'name' => 'Kabupaten Aceh Timur',
                'capital' => 'Idi Rayeuk',
                'latitude' => null,
                'longitude' => null,
                'elevation' => 0,
                'timezone' => 7,
                'area' => null,
                'population' => null,
            ],
        ]);

        $courier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        // Rate hanya tersedia untuk regency 1103,
        // sedangkan request menggunakan regency 1102.
        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1103',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Shipping Different Regency Category',
            'slug' => 'shipping-different-regency-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Different Regency Product',
            'slug' => 'shipping-different-regency-product',
            'description' => 'Product test.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
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
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 10,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Shipping Different Regency Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-different-regency-test',
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
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-different-regency-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'regency_id' => '1102',
            ],
        ]);

        $response->assertJsonFragment([
            'courier_code' => 'jnt_cargo',
            'available' => false,
            'weight' => null,
            'fee' => null,
        ]);
    }

    public function test_shipping_estimate_all_calculates_total_weight_from_multiple_products(): void
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
            'name' => 'Shipping Multiple Products Category',
            'slug' => 'shipping-multiple-products-category',
        ]);

        $productOne = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Multiple Product One',
            'slug' => 'shipping-multiple-product-one',
            'description' => 'Product test one.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 100,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $productOne->id,
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 8,
            'packing_weight' => 8.25,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $productTwo = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Multiple Product Two',
            'slug' => 'shipping-multiple-product-two',
            'description' => 'Product test two.',
            'product_detail' => null,
            'original_price' => 2000000,
            'discount_price' => null,
            'discount_percentage' => null,
            'is_sale' => false,
            'ready_stock' => 100,
            'locked_stock' => 0,
            'video_tutorial_url' => null,
            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        ProductSpecification::create([
            'product_id' => $productTwo->id,
            'dimensions' => '120 x 60 x 60 cm',
            'weight' => 12,
            'packing_weight' => 12.25,
            'load_capacity' => '70 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Shipping Multiple Products Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-multiple-products-test',
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $productOne->id,
            'quantity' => 2,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $productTwo->id,
            'quantity' => 1,
        ]);

        // 8.25 x 2 + 12.25 x 1 = 28.75 kg
        // ShippingService melakukan ceil => 29 kg.
        // 29 x 11.840 = Rp343.360.
        $response = $this->call(
            'POST',
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-multiple-products-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'regency_id' => '1102',
                'total_weight' => 28.75,
            ],
        ]);

        $response->assertJsonFragment([
            'courier_code' => 'jnt_cargo',
            'weight' => 29,
            'fee' => 343360,
            'available' => true,
        ]);
    }

    public function test_shipping_estimate_all_calculates_tiered_rate_correctly(): void
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
            'code' => 'tiered_cargo',
            'name' => 'Tiered Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'tiered',
            'price_per_kg' => null,
            'first_price' => 100000,
            'additional_price_per_kg' => 15000,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Shipping Tiered All Category',
            'slug' => 'shipping-tiered-all-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Tiered All Product',
            'slug' => 'shipping-tiered-all-product',
            'description' => 'Product test.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
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
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 12,
            'packing_weight' => 12,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Shipping Tiered All Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-tiered-test',
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
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-tiered-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'regency_id' => '1102',
                'total_weight' => 12,
            ],
        ]);

        $response->assertJsonFragment([
            'courier_code' => 'tiered_cargo',
            'weight' => 12,
            'fee' => 130000,
            'available' => true,
        ]);
    }

    public function test_shipping_estimate_all_tiered_rate_at_exactly_10kg_uses_first_price(): void
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
            'code' => 'tiered_10kg',
            'name' => 'Tiered 10KG Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'tiered',
            'price_per_kg' => null,
            'first_price' => 100000,
            'additional_price_per_kg' => 15000,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Shipping Tiered 10KG Category',
            'slug' => 'shipping-tiered-10kg-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Tiered 10KG Product',
            'slug' => 'shipping-tiered-10kg-product',
            'description' => 'Product test.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
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
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 10,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Shipping Tiered 10KG Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-tiered-10kg-test',
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
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-tiered-10kg-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'regency_id' => '1102',
                'total_weight' => 10,
            ],
        ]);

        $response->assertJsonFragment([
            'courier_code' => 'tiered_10kg',
            'weight' => 10,
            'fee' => 100000,
            'available' => true,
        ]);
    }

    public function test_shipping_estimate_all_tiered_rate_rounds_weight_before_calculation(): void
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
            'code' => 'tiered_rounding',
            'name' => 'Tiered Rounding Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courier->id,
            'regency_id' => '1102',
            'rate_type' => 'tiered',
            'price_per_kg' => null,
            'first_price' => 100000,
            'additional_price_per_kg' => 15000,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Shipping Tiered Rounding Category',
            'slug' => 'shipping-tiered-rounding-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping Tiered Rounding Product',
            'slug' => 'shipping-tiered-rounding-product',
            'description' => 'Product test.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
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
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 10.1,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Shipping Tiered Rounding Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-tiered-rounding-test',
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
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-tiered-rounding-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'regency_id' => '1102',
                'total_weight' => 10.1,
            ],
        ]);

        $response->assertJsonFragment([
            'courier_code' => 'tiered_rounding',
            'weight' => 11,
            'fee' => 115000,
            'available' => true,
        ]);
    }

    public function test_shipping_estimate_all_returns_all_active_couriers_as_unavailable_when_no_rates_exist(): void
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

        ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingCourier::create([
            'code' => 'sentral_cargo',
            'name' => 'Sentral Cargo',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Shipping No Rates Category',
            'slug' => 'shipping-no-rates-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'series_id' => null,
            'name' => 'Shipping No Rates Product',
            'slug' => 'shipping-no-rates-product',
            'description' => 'Product test.',
            'product_detail' => null,
            'original_price' => 1500000,
            'discount_price' => null,
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
            'dimensions' => '100 x 50 x 50 cm',
            'weight' => 10,
            'packing_weight' => 10,
            'load_capacity' => '50 kg',
            'assembly_required' => false,
        ]);

        $customer = Customer::create([
            'name' => 'Shipping No Rates Customer',
            'email' => null,
            'phone' => '081234567890',
            'address' => 'Alamat Test',
            'guest_token' => 'guest-token-shipping-all-no-rates-test',
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
            '/api/shipping/estimate-all',
            [],
            [
                config('customer.guest_cookie_name') =>
                    'guest-token-shipping-all-no-rates-test',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'regency_id' => '1102',
            ], JSON_THROW_ON_ERROR),
        );

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'regency_id' => '1102',
                'total_weight' => 10,
            ],
        ]);

        $response->assertJsonCount(2, 'data.couriers');

        $response->assertJsonFragment([
            'courier_code' => 'jnt_cargo',
            'available' => false,
            'weight' => null,
            'fee' => null,
        ]);

        $response->assertJsonFragment([
            'courier_code' => 'sentral_cargo',
            'available' => false,
            'weight' => null,
            'fee' => null,
        ]);
    }

    public function test_shipping_couriers_returns_active_couriers_with_active_rates(): void
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

        $activeCourier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $activeCourier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/shipping/couriers');

        $response->assertOk();

        $response->assertJson([
            'message' => 'Daftar courier berhasil diambil.',
        ]);

        $response->assertJsonFragment([
            'id' => $activeCourier->id,
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
        ]);
    }

    public function test_shipping_couriers_excludes_active_courier_without_active_rates(): void
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

        $activeWithRate = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $activeWithRate->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $activeWithoutRate = ShippingCourier::create([
            'code' => 'no_rate_cargo',
            'name' => 'No Rate Cargo',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/shipping/couriers');

        $response->assertOk();

        $response->assertJsonFragment([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
        ]);

        $response->assertJsonMissing([
            'code' => 'no_rate_cargo',
            'name' => 'No Rate Cargo',
        ]);
    }

    public function test_shipping_couriers_excludes_active_courier_with_only_inactive_rates(): void
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

        $activeWithActiveRate = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $activeWithActiveRate->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $activeWithInactiveRate = ShippingCourier::create([
            'code' => 'inactive_rate_cargo',
            'name' => 'Inactive Rate Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $activeWithInactiveRate->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 15000,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/shipping/couriers');

        $response->assertOk();

        $response->assertJsonFragment([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
        ]);

        $response->assertJsonMissing([
            'code' => 'inactive_rate_cargo',
            'name' => 'Inactive Rate Cargo',
        ]);
    }

    public function test_shipping_couriers_excludes_inactive_courier_with_active_rate(): void
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

        $activeCourier = ShippingCourier::create([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $activeCourier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 11840,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $inactiveCourier = ShippingCourier::create([
            'code' => 'inactive_cargo',
            'name' => 'Inactive Cargo',
            'is_active' => false,
        ]);

        ShippingRate::create([
            'courier_id' => $inactiveCourier->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 15000,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/shipping/couriers');

        $response->assertOk();

        $response->assertJsonFragment([
            'code' => 'jnt_cargo',
            'name' => 'J&T Cargo',
        ]);

        $response->assertJsonMissing([
            'code' => 'inactive_cargo',
            'name' => 'Inactive Cargo',
        ]);
    }

    public function test_shipping_couriers_are_ordered_by_name(): void
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

        $courierZ = ShippingCourier::create([
            'code' => 'z_cargo',
            'name' => 'Z Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courierZ->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 15000,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $courierA = ShippingCourier::create([
            'code' => 'a_cargo',
            'name' => 'A Cargo',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'courier_id' => $courierA->id,
            'regency_id' => '1102',
            'rate_type' => 'per_kg',
            'price_per_kg' => 12000,
            'first_price' => null,
            'additional_price_per_kg' => null,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/shipping/couriers');

        $response->assertOk();

        $couriers = $response->json('data');

        $this->assertCount(2, $couriers);

        $this->assertSame('A Cargo', $couriers[0]['name']);
        $this->assertSame('Z Cargo', $couriers[1]['name']);
    }

    public function test_shipping_couriers_returns_only_public_fields(): void
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

        $response = $this->getJson('/api/shipping/couriers');

        $response->assertOk();

        $response->assertJsonPath('data.0.id', $courier->id);
        $response->assertJsonPath('data.0.code', 'jnt_cargo');
        $response->assertJsonPath('data.0.name', 'J&T Cargo');

        $this->assertArrayNotHasKey(
            'is_active',
            $response->json('data.0')
        );

        $this->assertArrayNotHasKey(
            'created_at',
            $response->json('data.0')
        );

        $this->assertArrayNotHasKey(
            'updated_at',
            $response->json('data.0')
        );
    }

    public function test_shipping_couriers_returns_empty_data_when_no_courier_is_available(): void
    {
        $response = $this->getJson('/api/shipping/couriers');

        $response->assertOk();

        $response->assertJson([
            'message' => 'Daftar courier berhasil diambil.',
            'data' => [],
        ]);
    }   

    public function test_voucher_check_accepts_valid_voucher(): void
    {
        $voucher = \App\Models\Voucher::create([
            'code' => 'HEMAT10',
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

        $response = $this->postJson('/api/vouchers/check', [
            'code' => $voucher->code,
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'message' => 'Voucher berhasil diterapkan.',
            'code' => 'HEMAT10',
            'discount_type' => 'percentage',
            'discount_value' => '10.00',
            'max_discount_amount' => null,
            'min_order_amount' => '0.00',
            'estimated_discount' => 50000,
        ]);
    }

    public function test_voucher_check_rejects_unknown_voucher_code(): void
    {
        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'VOUCHER-TIDAK-ADA',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => false,
            'message' => 'Voucher tidak tersedia.',
        ]);
    }

    public function test_voucher_check_rejects_inactive_voucher(): void
    {
        $voucher = \App\Models\Voucher::create([
            'code' => 'NONAKTIF',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => $voucher->code,
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => false,
            'message' => 'Voucher tidak aktif.',
        ]);
    }

    public function test_voucher_check_rejects_voucher_before_start_date(): void
    {
        $voucher = \App\Models\Voucher::create([
            'code' => 'BELUMMULAI',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->addDay(),
            'expiry_date' => now()->addDays(2),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => $voucher->code,
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => false,
            'message' => 'Voucher belum mulai berlaku.',
        ]);
    }

    public function test_voucher_check_rejects_expired_voucher(): void
    {
        $voucher = \App\Models\Voucher::create([
            'code' => 'SUDAHEXPIRED',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDays(2),
            'expiry_date' => now()->subDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => $voucher->code,
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => false,
            'message' => 'Voucher sudah expired.',
        ]);
    }

    public function test_voucher_check_rejects_subtotal_below_minimum_order_amount(): void
    {
        $voucher = \App\Models\Voucher::create([
            'code' => 'MIN500',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 500000,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => $voucher->code,
            'subtotal' => 400000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => false,
            'message' => 'Minimal pembelian belum memenuhi.',
        ]);
    }

    public function test_voucher_check_rejects_voucher_when_usage_limit_reached(): void
    {
        $voucher = \App\Models\Voucher::create([
            'code' => 'LIMIT5',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => 5,
            'used_count' => 5,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => $voucher->code,
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => false,
            'message' => 'Batas penggunaan voucher sudah tercapai.',
        ]);
    }

    public function test_voucher_check_calculates_fixed_discount_correctly(): void
    {
        $voucher = \App\Models\Voucher::create([
            'code' => 'FIXED100',
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

        $response = $this->postJson('/api/vouchers/check', [
            'code' => $voucher->code,
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'message' => 'Voucher berhasil diterapkan.',
            'code' => 'FIXED100',
            'discount_type' => 'fixed',
            'discount_value' => '100000.00',
            'estimated_discount' => 100000,
        ]);
    }

    public function test_voucher_check_caps_percentage_discount_at_max_discount_amount(): void
    {
        $voucher = \App\Models\Voucher::create([
            'code' => 'MAX100',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'min_order_amount' => 0,
            'max_discount_amount' => 100000,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => $voucher->code,
            'subtotal' => 1000000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'MAX100',
            'discount_type' => 'percentage',
            'discount_value' => '20.00',
            'max_discount_amount' => '100000.00',
            'estimated_discount' => 100000,
        ]);
    }

    public function test_voucher_check_caps_fixed_discount_at_90_percent_of_subtotal(): void
    {
        $voucher = \App\Models\Voucher::create([
            'code' => 'FIXEDFULL',
            'discount_type' => 'fixed',
            'discount_value' => 500000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'FIXEDFULL',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'FIXEDFULL',
            'discount_type' => 'fixed',
            'discount_value' => '500000.00',
            'estimated_discount' => 450000,
        ]);
    }

    public function test_voucher_check_caps_percentage_discount_at_90_percent_of_subtotal(): void
    {
        $voucher = \App\Models\Voucher::create([
            'code' => 'PERCENT100',
            'discount_type' => 'percentage',
            'discount_value' => 100,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'PERCENT100',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'PERCENT100',
            'discount_type' => 'percentage',
            'discount_value' => '100.00',
            'estimated_discount' => 450000,
        ]);
    }

    public function test_voucher_check_requires_code(): void
    {
        $response = $this->postJson('/api/vouchers/check', [
            'subtotal' => 500000,
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'code',
        ]);

        $response->assertJsonPath(
            'errors.code.0',
            'Kode voucher wajib diisi.'
        );
    }
    
    public function test_voucher_check_requires_subtotal(): void
    {
        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'HEMAT10',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'subtotal',
        ]);

        $response->assertJsonPath(
            'errors.subtotal.0',
            'Subtotal wajib diisi.'
        );
    }

    public function test_voucher_check_rejects_code_longer_than_100_characters(): void
    {
        $response = $this->postJson('/api/vouchers/check', [
            'code' => str_repeat('A', 101),
            'subtotal' => 500000,
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'code',
        ]);

        $response->assertJsonPath(
            'errors.code.0',
            'Kode voucher maksimal 100 karakter.'
        );
    }

    public function test_voucher_check_rejects_non_numeric_subtotal(): void
    {
        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'HEMAT10',
            'subtotal' => 'abc',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'subtotal',
        ]);

        $response->assertJsonPath(
            'errors.subtotal.0',
            'Subtotal harus berupa angka.'
        );
    }

    public function test_voucher_check_rejects_negative_subtotal(): void
    {
        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'HEMAT10',
            'subtotal' => -1,
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'subtotal',
        ]);

        $response->assertJsonPath(
            'errors.subtotal.0',
            'Subtotal tidak boleh negatif.'
        );
    }

    public function test_voucher_check_calculates_percentage_discount_correctly(): void
    {
        \App\Models\Voucher::create([
            'code' => 'PERCENT15',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'PERCENT15',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'PERCENT15',
            'discount_type' => 'percentage',
            'discount_value' => '15.00',
            'estimated_discount' => 75000,
        ]);
    }

    public function test_voucher_check_keeps_90_percent_cap_when_max_discount_is_higher(): void
    {
        \App\Models\Voucher::create([
            'code' => 'MAX490',
            'discount_type' => 'percentage',
            'discount_value' => 100,
            'min_order_amount' => 0,
            'max_discount_amount' => 490000,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'MAX490',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'MAX490',
            'discount_type' => 'percentage',
            'discount_value' => '100.00',
            'max_discount_amount' => '490000.00',
            'estimated_discount' => 450000,
        ]);
    }

    public function test_voucher_check_accepts_subtotal_equal_to_minimum_order_amount(): void
    {
        \App\Models\Voucher::create([
            'code' => 'MINEXACT',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 500000,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'MINEXACT',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'MINEXACT',
            'estimated_discount' => 50000,
        ]);
    }

    public function test_voucher_check_accepts_voucher_when_usage_limit_not_reached(): void
    {
        \App\Models\Voucher::create([
            'code' => 'LIMITAVAILABLE',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => 5,
            'used_count' => 4,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'LIMITAVAILABLE',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'LIMITAVAILABLE',
            'estimated_discount' => 50000,
        ]);
    }

    public function test_voucher_check_accepts_voucher_without_start_date(): void
    {
        \App\Models\Voucher::create([
            'code' => 'NOSTART',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => null,
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'NOSTART',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'NOSTART',
            'estimated_discount' => 50000,
        ]);
    }

    public function test_voucher_check_accepts_voucher_without_usage_limit(): void
    {
        \App\Models\Voucher::create([
            'code' => 'UNLIMITED',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 999,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'UNLIMITED',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'UNLIMITED',
            'estimated_discount' => 50000,
        ]);
    }

    public function test_voucher_check_accepts_zero_subtotal(): void
    {
        \App\Models\Voucher::create([
            'code' => 'ZERO',
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

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'ZERO',
            'subtotal' => 0,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'ZERO',
            'estimated_discount' => 0,
        ]);
    }

    public function test_voucher_check_caps_fixed_discount_at_90_percent_when_discount_exceeds_subtotal(): void
    {
        \App\Models\Voucher::create([
            'code' => 'FIXED200',
            'discount_type' => 'fixed',
            'discount_value' => 200000,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'FIXED200',
            'subtotal' => 100000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'FIXED200',
            'discount_type' => 'fixed',
            'discount_value' => '200000.00',
            'estimated_discount' => 90000,
        ]);
    }

    public function test_voucher_check_fixed_discount_ignores_max_discount_amount(): void
    {
        \App\Models\Voucher::create([
            'code' => 'FIXEDMAX50',
            'discount_type' => 'fixed',
            'discount_value' => 200000,
            'min_order_amount' => 0,
            'max_discount_amount' => 50000,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'FIXEDMAX50',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'FIXEDMAX50',
            'discount_type' => 'fixed',
            'discount_value' => '200000.00',
            'max_discount_amount' => '50000.00',
            'estimated_discount' => 200000,
        ]);
    }

    public function test_voucher_check_treats_zero_max_discount_amount_as_no_cap(): void
    {
        \App\Models\Voucher::create([
            'code' => 'MAXZERO',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'min_order_amount' => 0,
            'max_discount_amount' => 0,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'MAXZERO',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'MAXZERO',
            'discount_type' => 'percentage',
            'discount_value' => '20.00',
            'max_discount_amount' => '0.00',
            'estimated_discount' => 100000,
        ]);
    }

    public function test_voucher_check_calculates_decimal_percentage_discount_correctly(): void
    {
        \App\Models\Voucher::create([
            'code' => 'DECIMAL15',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'DECIMAL15',
            'subtotal' => 333333,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'DECIMAL15',
            'estimated_discount' => 49999.95,
        ]);
    }

    public function test_voucher_check_calculates_decimal_fixed_discount_correctly(): void
    {
        \App\Models\Voucher::create([
            'code' => 'FIXEDDECIMAL',
            'discount_type' => 'fixed',
            'discount_value' => 123456.78,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'FIXEDDECIMAL',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'FIXEDDECIMAL',
            'discount_type' => 'fixed',
            'discount_value' => '123456.78',
            'estimated_discount' => 123456.78,
        ]);
    }

    public function test_voucher_check_accepts_max_discount_at_exact_90_percent_cap(): void
    {
        \App\Models\Voucher::create([
            'code' => 'MAXEXACT90',
            'discount_type' => 'percentage',
            'discount_value' => 100,
            'min_order_amount' => 0,
            'max_discount_amount' => 450000,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'MAXEXACT90',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'MAXEXACT90',
            'discount_type' => 'percentage',
            'discount_value' => '100.00',
            'max_discount_amount' => '450000.00',
            'estimated_discount' => 450000,
        ]);
    }

    public function test_voucher_check_accepts_zero_percentage_discount(): void
    {
        \App\Models\Voucher::create([
            'code' => 'ZERO_PERCENT',
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'ZERO_PERCENT',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'ZERO_PERCENT',
            'discount_type' => 'percentage',
            'discount_value' => '0.00',
            'estimated_discount' => 0,
        ]);
    }

    public function test_voucher_check_accepts_zero_fixed_discount(): void
    {
        \App\Models\Voucher::create([
            'code' => 'ZERO_FIXED',
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/vouchers/check', [
            'code' => 'ZERO_FIXED',
            'subtotal' => 500000,
        ]);

        $response->assertOk();

        $response->assertJson([
            'valid' => true,
            'code' => 'ZERO_FIXED',
            'discount_type' => 'fixed',
            'discount_value' => '0.00',
            'estimated_discount' => 0,
        ]);
    }


}