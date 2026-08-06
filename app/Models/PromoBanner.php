<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PromoBanner extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Image
        |--------------------------------------------------------------------------
        */

        'image',

        /*
        |--------------------------------------------------------------------------
        | Accessibility
        |--------------------------------------------------------------------------
        */
        'url',
        
        'alt',

        /*
        |--------------------------------------------------------------------------
        | Display
        |--------------------------------------------------------------------------
        */

        'sort_order',

        'is_active',

    ];

    protected $casts = [

        'is_active' => 'boolean',

        'sort_order' => 'integer',

    ];
}