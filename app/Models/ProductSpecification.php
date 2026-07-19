<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProductSpecification extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'dimensions',
        'seat_height',
        'load_capacity',
        'material_details'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
