<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartVariantTest extends TestCase
{
    use RefreshDatabase;

    protected function category(): Category
    {
        return Category::firstOrCreate(
            ['slug' => 'furniture'],
            ['name' => 'Furniture']
        );
    }

    public function test_different_variants_of_same_product_create_separate_cart_items(): void
    {
        $customer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => 'test-cart-variant-guest',
        ]);

        $product = Product::create([
            'category_id' => $this->category()->id,
            'name' => 'Produk Variant Test',
            'sku' => 'TEST-CART-VARIANT-001',
            'slug' => 'produk-cart-variant-test',
            'description' => 'Produk test cart variant.',
            'original_price' => 1000000,
            'ready_stock' => 0,
            'locked_stock' => 0,
            'origin_city' => 'Malang',
            'is_sale' => false,
        ]);

        $red = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Merah',
            'ready_stock' => 10,
            'locked_stock' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $blue = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Biru',
            'ready_stock' => 10,
            'locked_stock' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $service = app(CartService::class);

        $service->addItem(
            customer: $customer,
            product: $product,
            quantity: 2,
            variant: $red,
        );

        $service->addItem(
            customer: $customer,
            product: $product,
            quantity: 3,
            variant: $blue,
        );

        $cart = Cart::where('customer_id', $customer->id)
            ->firstOrFail();

        $this->assertDatabaseCount('cart_items', 2);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $red->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $blue->id,
            'quantity' => 3,
        ]);
    }

    public function test_same_variant_increments_existing_cart_item_quantity(): void
    {
        $customer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => 'test-cart-same-variant-guest',
        ]);

        $product = Product::create([
            'category_id' => $this->category()->id,
            'name' => 'Produk Same Variant Test',
            'sku' => 'TEST-CART-SAME-VARIANT-001',
            'slug' => 'produk-cart-same-variant-test',
            'description' => 'Produk test same variant.',
            'original_price' => 1000000,
            'ready_stock' => 0,
            'locked_stock' => 0,
            'origin_city' => 'Malang',
            'is_sale' => false,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 10,
            'locked_stock' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = app(CartService::class);

        $service->addItem(
            customer: $customer,
            product: $product,
            quantity: 2,
            variant: $variant,
        );

        $service->addItem(
            customer: $customer,
            product: $product,
            quantity: 3,
            variant: $variant,
        );

        $cart = Cart::where('customer_id', $customer->id)
            ->firstOrFail();

        $this->assertDatabaseCount('cart_items', 1);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);
    }

    public function test_cannot_add_more_than_variant_ready_stock(): void
    {
        $customer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => 'test-cart-variant-stock-guest',
        ]);

        $product = Product::create([
            'category_id' => $this->category()->id,
            'name' => 'Produk Variant Stock Test',
            'sku' => 'TEST-CART-VARIANT-STOCK-001',
            'slug' => 'produk-cart-variant-stock-test',
            'description' => 'Produk test variant stock.',
            'original_price' => 1000000,
            'ready_stock' => 0,
            'locked_stock' => 0,
            'origin_city' => 'Malang',
            'is_sale' => false,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 5,
            'locked_stock' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = app(CartService::class);

        $service->addItem(
            customer: $customer,
            product: $product,
            quantity: 3,
            variant: $variant,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stok produk tidak mencukupi.');

        $service->addItem(
            customer: $customer,
            product: $product,
            quantity: 3,
            variant: $variant,
        );
    }

    public function test_update_item_uses_variant_ready_stock(): void
    {
        $customer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => 'test-cart-update-variant-guest',
        ]);

        $product = Product::create([
            'category_id' => $this->category()->id,
            'name' => 'Produk Update Variant Test',
            'sku' => 'TEST-CART-UPDATE-VARIANT-001',
            'slug' => 'produk-cart-update-variant-test',
            'description' => 'Produk test update variant.',
            'original_price' => 1000000,
            'ready_stock' => 0,
            'locked_stock' => 0,
            'origin_city' => 'Malang',
            'is_sale' => false,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 5,
            'locked_stock' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = app(CartService::class);

        $service->addItem(
            customer: $customer,
            product: $product,
            quantity: 2,
            variant: $variant,
        );

        $cart = Cart::where('customer_id', $customer->id)
            ->firstOrFail();

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->firstOrFail();

        $service->updateItem($cartItem, 5);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stok produk tidak mencukupi.');

        $service->updateItem($cartItem, 6);
    }

    public function test_cannot_add_variant_belonging_to_another_product(): void
    {
        $customer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => 'test-cart-wrong-variant-product-guest',
        ]);

        $product = Product::create([
            'category_id' => $this->category()->id,
            'name' => 'Produk A',
            'sku' => 'TEST-CART-WRONG-VARIANT-A',
            'slug' => 'produk-cart-wrong-variant-a',
            'description' => 'Produk A.',
            'original_price' => 1000000,
            'ready_stock' => 0,
            'locked_stock' => 0,
            'origin_city' => 'Malang',
            'is_sale' => false,
        ]);

        $otherProduct = Product::create([
            'category_id' => $this->category()->id,
            'name' => 'Produk B',
            'sku' => 'TEST-CART-WRONG-VARIANT-B',
            'slug' => 'produk-cart-wrong-variant-b',
            'description' => 'Produk B.',
            'original_price' => 1000000,
            'ready_stock' => 0,
            'locked_stock' => 0,
            'origin_city' => 'Malang',
            'is_sale' => false,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $otherProduct->id,
            'name' => 'Natural',
            'ready_stock' => 10,
            'locked_stock' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = app(CartService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Varian produk tidak sesuai dengan produk.');

        $service->addItem(
            customer: $customer,
            product: $product,
            quantity: 1,
            variant: $variant,
        );
    }

    public function test_variant_is_required_for_product_with_active_variants(): void
    {
        $customer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => 'test-cart-required-variant-guest',
        ]);

        $product = Product::create([
            'category_id' => $this->category()->id,
            'name' => 'Produk Required Variant Test',
            'sku' => 'TEST-CART-REQUIRED-VARIANT-001',
            'slug' => 'produk-cart-required-variant-test',
            'description' => 'Produk test required variant.',
            'original_price' => 1000000,
            'ready_stock' => 0,
            'locked_stock' => 0,
            'origin_city' => 'Malang',
            'is_sale' => false,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 10,
            'locked_stock' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = app(CartService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Varian produk wajib dipilih.');

        $service->addItem(
            customer: $customer,
            product: $product,
            quantity: 1,
        );
    }

    public function test_api_can_add_product_variant_to_cart(): void
    {
        $customer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => 'test-api-cart-variant-guest',
        ]);

        $product = Product::create([
            'category_id' => $this->category()->id,
            'name' => 'Produk API Variant Test',
            'sku' => 'TEST-API-CART-VARIANT-001',
            'slug' => 'produk-api-cart-variant-test',
            'description' => 'Produk test API cart variant.',
            'original_price' => 1000000,
            'ready_stock' => 0,
            'locked_stock' => 0,
            'origin_city' => 'Malang',
            'is_sale' => false,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 10,
            'locked_stock' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->withHeader(
            'X-Guest-Session-Id',
            $customer->guest_token
        )->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.items.0.product_variant.id',
            $variant->id
        );

        $response->assertJsonPath(
            'data.items.0.product_variant.name',
            'Natural'
        );

        $response->assertJsonPath(
            'data.items.0.quantity',
            2
        );

        $response->assertJsonPath(
            'data.items.0.product.stock',
            10
        );

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => Cart::where('customer_id', $customer->id)->firstOrFail()->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_api_can_update_product_variant_cart_item(): void
    {
        $customer = Customer::create([
            'name' => null,
            'phone' => null,
            'email' => null,
            'address_detail' => null,
            'guest_token' => 'test-api-update-cart-variant-guest',
        ]);

        $product = Product::create([
            'category_id' => $this->category()->id,
            'name' => 'Produk API Update Variant Test',
            'sku' => 'TEST-API-UPDATE-CART-VARIANT-001',
            'slug' => 'produk-api-update-cart-variant-test',
            'description' => 'Produk test update API cart variant.',
            'original_price' => 1000000,
            'ready_stock' => 0,
            'locked_stock' => 0,
            'origin_city' => 'Malang',
            'is_sale' => false,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 10,
            'locked_stock' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->withHeader(
            'X-Guest-Session-Id',
            $customer->guest_token
        )->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertStatus(200);

        $cartItem = CartItem::where('product_id', $product->id)
            ->where('product_variant_id', $variant->id)
            ->firstOrFail();

        $response = $this->withHeader(
            'X-Guest-Session-Id',
            $customer->guest_token
        )->patchJson(
            "/api/cart/items/{$cartItem->id}",
            [
                'quantity' => 5,
            ]
        );

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.items.0.product_variant.id',
            $variant->id
        );

        $response->assertJsonPath(
            'data.items.0.product_variant.name',
            'Natural'
        );

        $response->assertJsonPath(
            'data.items.0.quantity',
            5
        );

        $response->assertJsonPath(
            'data.items.0.product.stock',
            10
        );

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);
    }
}