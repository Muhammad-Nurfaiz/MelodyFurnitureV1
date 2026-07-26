<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform resource.
     */
    public function toArray(Request $request): array
    {
        $subtotal = $this->items->sum(function ($item) {

            $price = $item->product->discount_price
                ?? $item->product->original_price;

            return $price * $item->quantity;

        });

        return [

            'id' => $this->id,

            /*
            |--------------------------------------------------------------------------
            | Summary
            |--------------------------------------------------------------------------
            */

            'total_items' => $this->items->count(),

            'total_quantity' => $this->items->sum('quantity'),

            'subtotal' => $subtotal,

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => CartItemResource::collection(
                $this->items
            ),

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            'created_at' => optional(
                $this->created_at
            )?->toISOString(),

            'updated_at' => optional(
                $this->updated_at
            )?->toISOString(),

        ];
    }
}