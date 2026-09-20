<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' => $this->id,

            'name' => $this->name,

            'slug' => $this->slug,

            'description' => $this->description,

            'product_detail' => $this->product_detail,

            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            'original_price' => (float) $this->original_price,

            'discount_price' => $this->discount_price !== null
                ? (float) $this->discount_price
                : null,

            'discount_percentage' => $this->discount_percentage,

            'is_sale' => (bool) $this->is_sale,

            'price' => $this->price,

            'formatted_price' => $this->formatted_price,

            /*
            |--------------------------------------------------------------------------
            | Sales Information
            |--------------------------------------------------------------------------
            */

            'average_rating' => (float) $this->average_rating,

            'total_stock' => (int) $this->ready_stock,

            'total_sold' => (int) $this->total_sold,

            'video_tutorial_url' => $this->video_tutorial_url ?? null,

            'origin_city' => $this->origin_city,

            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            'category' => $this->whenLoaded(
                'category',
                fn () => [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ]
            ),

            /*
            |--------------------------------------------------------------------------
            | Series
            |--------------------------------------------------------------------------
            */

            'series' => $this->whenLoaded(
                'series',
                fn () => $this->series
                    ? [
                        'id' => $this->series->id,
                        'name' => $this->series->name,
                        'slug' => $this->series->slug,
                        'description' => $this->series->description,
                    ]
                    : null
            ),

            /*
            |--------------------------------------------------------------------------
            | Specification
            |--------------------------------------------------------------------------
            */

            'specification' => $this->whenLoaded(
                'specification',
                fn () => $this->specification
                    ? [
                        'dimensions' => $this->specification->dimensions,
                        'weight' => (float) $this->specification->weight,
                        'packing_weight' => (float) $this->specification->packing_weight,
                        'load_capacity' => $this->specification->load_capacity,
                        'assembly_required' => (bool) $this->specification->assembly_required,
                    ]
                    : null
            ),

            /*
            |--------------------------------------------------------------------------
            | Media
            |--------------------------------------------------------------------------
            */

            'media' => ProductMediaResource::collection(
                $this->whenLoaded('media')
            ),

            /*
            |--------------------------------------------------------------------------
            | Recommended Products
            |--------------------------------------------------------------------------
            |
            | Akan diisi oleh ProductRecommendationService.
            |
            */

            'recommended_products' => ProductCardResource::collection(
                $this->when(
                    isset($this->recommendedProducts),
                    $this->recommendedProducts
                )
            ),
        ];
    }
}