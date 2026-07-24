<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            */

            'id' => $this->product_id,
            'name' => $this->product_name,
            'slug' => $this->product_slug,
            'thumbnail' => $this->product_thumbnail,

            /*
            |--------------------------------------------------------------------------
            | Order
            |--------------------------------------------------------------------------
            */

            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,

            /*
            |--------------------------------------------------------------------------
            | Weight
            |--------------------------------------------------------------------------
            */

            'weight' => $this->weight,
            'total_weight' => $this->total_weight,
        ];
    }
}