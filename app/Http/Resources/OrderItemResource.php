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
            'slug' => $this->product_slug,
            'thumbnail' => $this->product_thumbnail,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,
            'weight' => $this->weight,
            'total_weight' => $this->total_weight,
        ];
    }
}