<?php

namespace Tests\Feature\Admin\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\Series;
use App\Services\Media\TemporaryMediaService;
use App\Services\Product\ProductMediaService;
use App\Services\Product\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProductService::class);
    }

    protected function category(): Category
    {
        return Category::firstOrCreate(
            ['slug' => 'furniture'],
            ['name' => 'Furniture']
        );
    }

    protected function productData(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->category()->id,
            'series_id' => null,
            'name' => 'Produk Test Variant',
            'sku' => 'TEST-VARIANT-001',
            'description' => 'Produk untuk testing variant.',
            'product_detail' => null,
            'original_price' => 1000000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 0,
            'variants_enabled' => true,
            'variants' => [
                [
                    'name' => 'Natural',
                    'ready_stock' => 10,
                ],
            ],
            'dimensions' => '100 x 100 x 100 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => 100,
            'assembly_required' => false,
            'temporary_media' => [],
            'media_order' => [],
            'main_media' => null,
        ], $overrides);
    }

    public function test_can_create_product_with_variants(): void
    {
        $product = $this->service->create(
            $this->productData()
        );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'ready_stock' => 0,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 10,
            'locked_stock' => 0,
            'is_active' => 1,
            'sort_order' => 0,
        ]);
    }

    public function test_can_update_existing_variant_stock_and_name(): void
    {
        $product = $this->service->create(
            $this->productData()
        );

        $variant = $product->variants->first();

        $this->service->update(
            $product,
            $this->productData([
                'name' => 'Produk Test Variant Updated',
                'variants' => [
                    [
                        'id' => $variant->id,
                        'name' => 'Walnut',
                        'ready_stock' => 20,
                    ],
                ],
            ])
        );

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'product_id' => $product->id,
            'name' => 'Walnut',
            'ready_stock' => 20,
            'locked_stock' => 0,
            'sort_order' => 0,
        ]);
    }

    public function test_can_add_new_variant_without_removing_existing_variant(): void
    {
        $product = $this->service->create(
            $this->productData()
        );

        $existingVariant = $product->variants->first();

        $this->service->update(
            $product,
            $this->productData([
                'variants' => [
                    [
                        'id' => $existingVariant->id,
                        'name' => 'Natural',
                        'ready_stock' => 10,
                    ],
                    [
                        'name' => 'Walnut',
                        'ready_stock' => 5,
                    ],
                ],
            ])
        );

        $this->assertDatabaseCount('product_variants', 2);

        $this->assertDatabaseHas('product_variants', [
            'id' => $existingVariant->id,
            'name' => 'Natural',
            'ready_stock' => 10,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => 'Walnut',
            'ready_stock' => 5,
        ]);
    }

    public function test_cannot_convert_product_with_existing_stock_to_variant_product(): void
    {
        $product = Product::create([
            'category_id' => $this->category()->id,
            'name' => 'Produk Non Variant',
            'sku' => 'TEST-NON-VARIANT-001',
            'slug' => 'produk-non-variant',
            'description' => 'Produk test.',
            'original_price' => 1000000,
            'ready_stock' => 10,
            'locked_stock' => 0,
            'origin_city' => 'Malang',
            'is_sale' => false,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->update(
            $product,
            $this->productData([
                'name' => $product->name,
                'sku' => $product->sku,
                'variants' => [
                    [
                        'name' => 'Natural',
                        'ready_stock' => 10,
                    ],
                ],
            ])
        );
    }
}