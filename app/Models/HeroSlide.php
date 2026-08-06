<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Content
        |--------------------------------------------------------------------------
        */

        'image',

        'eyebrow',

        'title',

        'description',

        /*
        |--------------------------------------------------------------------------
        | CTA
        |--------------------------------------------------------------------------
        */

        'button_text',

        'button_url',

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