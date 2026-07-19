<?php

namespace App\Services\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Media\TemporaryMediaService;

class ProductService
{
    /**
     * Create Product
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {

            $product = $this->createProduct($data);

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
            ]);
        });
    }

    /**
     * Update Product
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {

            $this->updateProduct($product, $data);

            $this->updateSpecification($product, $data);

            /*
            |--------------------------------------------------------------------------
            | Delete Media Lama
            |--------------------------------------------------------------------------
            */

            if (!empty($data['deleted_media'])) {

                $this->mediaService->deleteMedia(
                    $product,
                    $data['deleted_media']
                );

            }

            /*
            |--------------------------------------------------------------------------
            | Upload Gallery Baru
            |--------------------------------------------------------------------------
            */

            

            /*
            |--------------------------------------------------------------------------
            | Sync Media
            |--------------------------------------------------------------------------
            */

            $this->mediaService->attachTemporaryMedia(

                $product,

                $data['temporary_media'] ?? [],

                $data['media_order'] ?? [],

                $data['main_media'] ?? null,

            );

            /*
            |--------------------------------------------------------------------------
            | Update Thumbnail
            |--------------------------------------------------------------------------
            */

            // if (!empty($data['main_media'])) {

            //     $this->mediaService->setMainMedia(
            //         $product,
            //         $data['main_media']
            //     );

            // }

            /*
            |--------------------------------------------------------------------------
            | Update Sorting
            |--------------------------------------------------------------------------
            */

            // if (!empty($data['media_order'])) {

            //     $this->mediaService->updateSortOrder(
            //         $product,
            //         $data['media_order']
            //     );

            // }

            return $product->fresh([
                'category',
                'series',
                'thumbnail',
                'media',
                'specification',
            ]);

        });
    }

    protected function updateProduct(
        Product $product,
        array $data
    ): void {

        $originalPrice = (float) $data['original_price'];
        $discountPrice = $data['discount_price'];

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

            'series_id' => $data['series_id'],

            'name' => $data['name'],

            'slug' => $this->generateUniqueSlug(
                filled($data['slug'])
                    ? $data['slug']
                    : $data['name'],
                $product
            ),

            'description' => $data['description'],

            'product_detail' => $data['product_detail'],
                
            'original_price' => $originalPrice,

            'discount_price' => $discountPrice,

            'discount_percentage' => $discountPercentage,

            'is_sale' => $discountPercentage > 0,

            'ready_stock' => $data['ready_stock'],

            'locked_stock' => $data['locked_stock'],

            'origin_city' => $data['origin_city'],

            'video_tutorial_url' => $data['video_tutorial_url'],

            'average_rating' => $data['average_rating'],

            'total_sold' => $data['total_sold'],

        ]);

    }

    protected function updateSpecification(
        Product $product,
        array $data
    ): void {

        $product->specification()->update([

            'dimensions' => $data['dimensions'],

            'seat_height' => $data['seat_height'],

            'load_capacity' => $data['load_capacity'],

            'material_details' => $data['material_details'],

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

            'slug' => $this->generateUniqueSlug(
                filled($data['slug'])
                    ? $data['slug']
                    : $data['name']
            ),

            'description' => $data['description'],

            'product_detail' => $data['product_detail'] ?? null,

            'original_price' => $originalPrice,

            'discount_price' => $discountPrice,

            'discount_percentage' => $discountPercentage,

            'is_sale' => $discountPercentage > 0,

            'ready_stock' => $data['ready_stock'],

            'locked_stock' => $data['locked_stock'] ?? 0,

            'video_tutorial_url' => $data['video_tutorial_url'] ?? null,

            'origin_city' => $data['origin_city'] ?? null,

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

            'seat_height' => $data['seat_height'],

            'load_capacity' => $data['load_capacity'],

            'material_details' => $data['material_details'],

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
}