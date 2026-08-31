<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Digunakan untuk:
     * - Product catalog
     * - Search result
     * - Filter result
     * - Recommended products
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,

            'slug' => $this->slug,

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
            | Product Information
            |--------------------------------------------------------------------------
            */

            'average_rating' => (float) $this->average_rating,

            'total_sold' => (int) $this->total_sold,

            'origin_city' => $this->origin_city,

            /*
            |--------------------------------------------------------------------------
            | Thumbnail
            |--------------------------------------------------------------------------
            |
            | Hanya media utama yang dikirim pada product card.
            |
            */

            'thumbnail' => $this->whenLoaded(
                'thumbnail',
                fn () => $this->thumbnail
                    ? [
                        'id' => $this->thumbnail->id,
                        'url' => $this->thumbnail->url,
                        'alt_text' => $this->thumbnail->alt_text,
                    ]
                    : null
            ),

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
                    ]
                    : null
            ),
        ];
    }
}