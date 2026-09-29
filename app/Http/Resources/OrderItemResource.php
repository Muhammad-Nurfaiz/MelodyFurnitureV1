<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product_id,
            'name' => $this->product_name,
            'product_variant_id' => $this->product_variant_id,
            'product_variant_name' => $this->product_variant_name,
            'slug' => $this->product_slug,
            'image' => $this->product_image,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,
        ];
    }
}