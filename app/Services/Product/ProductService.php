<?php

namespace App\Services\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Product\ProductMediaService;
use App\Services\Media\TemporaryMediaService;
use Illuminate\Validation\ValidationException;

class ProductService
{
    /**
     * Create Product
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {

            $product = $this->createProduct($data);

            if ($data['variants_enabled']) {
                $this->syncVariants($product, $data['variants']);
            }

            $this->createSpecification($product, $data);

            $this->mediaService->attachTemporaryMedia(

                $product,

                $data['temporary_media'] ?? [],

                $data['media_order'] ?? [],

                $data['main_media'] ?? null,

            );

            return $product->fresh([
                'category',
                'series',
                'thumbnail',
                'media',
                'specification',
                'variants',
            ]);
        });
    }

    /**
     * Update Product
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {

            $this->syncVariantsOnUpdate(
                $product,
                $data['variants_enabled'],
                $data['variants'] ?? []
            );

            $this->updateProduct($product, $data);

            $this->updateSpecification($product, $data);

            if (!empty($data['deleted_media'])) {
                $this->mediaService->deleteMedia(
                    $product,
                    $data['deleted_media']
                );
            }

            $this->mediaService->attachTemporaryMedia(
                $product,
                $data['temporary_media'] ?? [],
                $data['media_order'] ?? [],
                $data['main_media'] ?? null,
            );

            return $product->fresh([
                'category',
                'series',
                'thumbnail',
                'media',
                'specification',
                'variants',
            ]);
        });
    }

    protected function updateProduct(Product $product, array $data): void {
        $originalPrice = (float) $data['original_price'];

        $discountPrice = filled($data['discount_price'])
            ? (float) $data['discount_price']
            : null;

        $discountPercentage = null;

        if (
            $discountPrice !== null &&
            $discountPrice > 0 &&
            $discountPrice < $originalPrice
        ) {
            $discountPercentage = round(
                (($originalPrice - $discountPrice) / $originalPrice) * 100
            );
        }

        $product->update([
            'category_id' => $data['category_id'],
            'series_id' => $data['series_id'] ?? null,
            'name' => $data['name'],
            'sku' => strtoupper(trim($data['sku'])),
            'slug' => $this->generateUniqueSlug(
                $data['name'],
                $product
            ),
            'description' => $data['description'],
            'product_detail' => $data['product_detail'] ?? null,
            'original_price' => $originalPrice,
            'discount_price' => $discountPrice,
            'discount_percentage' => $discountPercentage,

            // PERBAIKAN DI SINI: Gunakan input dari request, fallback ke false jika kosong
            'is_sale' => (bool) ($data['is_sale'] ?? false),

            'ready_stock' => $data['variants_enabled']
                ? 0
                : $data['ready_stock'],
            'video_tutorial_url' => $data['video_tutorial_url'] ?? null,
            'average_rating' => $data['average_rating'] ?? 0,
            'total_sold' => $data['total_sold'] ?? 0,
        ]);
    }

    protected function updateSpecification(Product $product,array $data): void {

        $product->specification()->update([

            'dimensions' => $data['dimensions'],

            'weight' => $data['weight'],

            'packing_weight' => $data['packing_weight'],

            'load_capacity' => $data['load_capacity'],

            'assembly_required' =>
                $data['assembly_required'] ?? false,

        ]);
    }

    protected function createProduct(array $data): Product
    {
        $originalPrice = (float) $data['original_price'];

        $discountPrice = filled($data['discount_price'])
            ? (float) $data['discount_price']
            : null;

        $discountPercentage = null;

        if (
            $discountPrice &&
            $discountPrice < $originalPrice
        ) {
            $discountPercentage = round(
                (($originalPrice - $discountPrice) / $originalPrice) * 100
            );
        }

        return Product::create([
            'category_id' => $data['category_id'],
            'series_id' => $data['series_id'] ?? null,
            'name' => $data['name'],
            'sku' => strtoupper(trim($data['sku'])),
            'slug' => $this->generateUniqueSlug($data['name']),
            'description' => $data['description'],
            'product_detail' => $data['product_detail'] ?? null,
            'original_price' => $originalPrice,
            'discount_price' => $discountPrice,
            'discount_percentage' => $discountPercentage,

            // PERBAIKAN DI SINI JUGA:
            'is_sale' => (bool) ($data['is_sale'] ?? false),

            'ready_stock' => $data['ready_stock'],
            'locked_stock' => 0,
            'origin_city' => 'Malang',
            'video_tutorial_url' => $data['video_tutorial_url'] ?? null,
            'average_rating' => $data['average_rating'] ?? 0,
            'total_sold' => $data['total_sold'] ?? 0,
        ]);
    }

    /**
     * --------------------------------------------------------------------------
     * Specification
     * --------------------------------------------------------------------------
     */

    protected function createSpecification(
        Product $product,
        array $data
    ): void {

        $product->specification()->create([
            'dimensions' => $data['dimensions'],
            'weight' => $data['weight'],
            'packing_weight' => $data['packing_weight'],
            'load_capacity' => $data['load_capacity'],
            'assembly_required' => $data['assembly_required'] ?? false,
        ]);
    }

    protected ProductMediaService $mediaService;

    protected TemporaryMediaService $temporaryMediaService;

    public function __construct(
        ProductMediaService $mediaService,
        TemporaryMediaService $temporaryMediaService,
    ) {
        $this->mediaService = $mediaService;
        $this->temporaryMediaService = $temporaryMediaService;
    }

    /**
     * |--------------------------------------------------------------------------
     * Generate Unique Slug
     * |--------------------------------------------------------------------------
     */
    protected function generateUniqueSlug(
        string $value,
        ?Product $ignore = null
    ): string {
        $slug = Str::slug($value);
        $originalSlug = $slug;
        $counter = 2;
        while (
            Product::query()
                ->when(
                    $ignore,
                    fn ($query) => $query->whereKeyNot($ignore->id)
                )
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }
        return $slug;
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {

            // hapus seluruh gallery
            $this->mediaService->deleteAll($product);

            // hapus specification
            $product->specification()?->delete();

            // hapus produk
            $product->delete();

        });
    }

    private function syncVariants(Product $product, array $variants): void
    {
        foreach ($variants as $index => $variant) {
            $product->variants()->create([
                'name' => trim($variant['name']),
                'ready_stock' => $variant['ready_stock'],
                'locked_stock' => 0,
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }

    private function syncVariantsOnUpdate(Product $product,bool $variantsEnabled,array $variants): void 
    {
        $existingVariants = $product->variants()
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // Produk tidak menggunakan varian
        if (!$variantsEnabled) {
            foreach ($existingVariants as $variant) {
                if ($variant->locked_stock > 0) {
                    throw ValidationException::withMessages([
                        'variants' => "Varian {$variant->name} masih memiliki stok yang dikunci.",
                    ]);
                }
            }

            $product->variants()->delete();

            return;
        }

        // Produk berubah dari stok biasa menjadi menggunakan varian.
        // Stok produk lama tidak boleh hilang secara otomatis.
        if (
            $existingVariants->isEmpty() &&
            (
                $product->ready_stock > 0 ||
                $product->locked_stock > 0
            )
        ) {
            throw ValidationException::withMessages([
                'variants' => 'Produk yang sudah memiliki stok tidak dapat langsung diubah menjadi produk bervarian. Habiskan atau atur stok produk terlebih dahulu.',
            ]);
        }

        $submittedIds = collect($variants)
            ->pluck('id')
            ->filter()
            ->values();

        // Varian lama yang tidak lagi dikirim berarti dihapus.
        foreach ($existingVariants as $variant) {
            if ($submittedIds->contains($variant->id)) {
                continue;
            }

            if ($variant->locked_stock > 0) {
                throw ValidationException::withMessages([
                    'variants' => "Varian {$variant->name} masih memiliki stok yang dikunci dan tidak dapat dihapus.",
                ]);
            }

            $variant->delete();
        }

        // Update varian lama / buat varian baru
        foreach ($variants as $index => $variantData) {
            $variantId = $variantData['id'] ?? null;

            if ($variantId) {
                $variant = $existingVariants->get($variantId);

                if (!$variant) {
                    throw ValidationException::withMessages([
                        'variants' => 'Varian tidak valid untuk produk ini.',
                    ]);
                }

                $variant->update([
                    'name' => trim($variantData['name']),
                    'ready_stock' => $variantData['ready_stock'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]);

                continue;
            }

            $product->variants()->create([
                'name' => trim($variantData['name']),
                'ready_stock' => $variantData['ready_stock'],
                'locked_stock' => 0,
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }
}