<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform resource.
     */
    public function toArray(Request $request): array
    {
        $price = $this->product->discount_price
            ?? $this->product->original_price;

        return [

            'id' => $this->id,

            'quantity' => $this->quantity,

            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            'unit_price' => $price,

            'subtotal' => $price * $this->quantity,

            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            */

            'product' => [

                'id' => $this->product->id,

                'name' => $this->product->name,

                'slug' => $this->product->slug,

                'thumbnail' => optional(
                    $this->product->thumbnail
                )->url,

                'stock' => $this->product->ready_stock,

                'is_sale' => $this->product->is_sale,

            ],

        ];
    }
}