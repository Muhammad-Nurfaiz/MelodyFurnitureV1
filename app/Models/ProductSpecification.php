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
        'weight',
        'packing_weight',
        'load_capacity',
        'material_details',
        'assembly_required',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'packing_weight' => 'decimal:2',
        'assembly_required' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
