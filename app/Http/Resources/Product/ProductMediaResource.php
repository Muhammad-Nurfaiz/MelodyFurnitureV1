<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductMediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'media_type' => $this->media_type,

            'url' => $this->url,

            'thumbnail_url' => $this->thumbnail_url,

            'alt_text' => $this->alt_text,

            'is_main' => (bool) $this->is_main,

            'sort_order' => (int) $this->sort_order,
        ];
    }
}