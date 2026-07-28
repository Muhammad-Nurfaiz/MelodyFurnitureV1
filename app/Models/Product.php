<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'jubelio_variant_id',
        'category_id',
        'series_id',
        'name',
        'slug',
        'description',
        'product_detail',
        'original_price',
        'discount_price',
        'discount_percentage',
        'is_sale',
        'ready_stock',
        'locked_stock',
        'video_tutorial_url',
        'origin_city',
        'average_rating',
        'total_sold'
    ];
    
    protected $casts = [
        'original_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_sale' => 'boolean',
        'average_rating' => 'decimal:1',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_price',
    ];

    public function thumbnail()
    {
        return $this->hasOne(ProductMedia::class)
            ->where('is_main', true);
    }

    public function getFormattedPriceAttribute()
    {
        $price = $this->discount_price ?? $this->original_price;
        return 'Rp '.number_format($price, 0, ',', '.');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function media()
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function specification()
    {
        return $this->hasOne(ProductSpecification::class);
    }

    public function gallery()
    {
        return $this->hasMany(ProductMedia::class)
            ->where('media_type', 'image')
            ->where('is_main', false)
            ->orderBy('sort_order');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeSearch($query, $search)
    {
        return $query->when($search, function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    }

    public function scopeCategory($query, $categoryId)
    {
        return $query->when($categoryId, function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    public function scopeSeries($query, $seriesId)
    {
        return $query->when($seriesId, function ($q) use ($seriesId) {
            $q->where('series_id', $seriesId);
        });
    }

    public function scopeSale($query)
    {
        return $query->where('is_sale', true);
    }

    protected static function booted()
    {
        static::creating(function ($product) {
            if (!$product->slug) {
                $product->slug = \Str::slug($product->name);
            }
        });
    }

    public function getPriceAttribute(): float
    {
        if ($this->is_sale && !is_null($this->discount_price)) {
            return (float) $this->discount_price;
        }
        return (float) $this->original_price;
    }
}