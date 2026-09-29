<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductMedia extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'media_type',
        'media_url',
        'thumbnail_url',
        'alt_text',
        'is_main',
        'sort_order',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'url',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'media_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->media_url);
    }
}