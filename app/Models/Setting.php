<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Store Identity
        |--------------------------------------------------------------------------
        */

        'store_name',

        'store_description',

        'store_logo',

        'store_favicon',

        /*
        |--------------------------------------------------------------------------
        | Social Media
        |--------------------------------------------------------------------------
        */

        'instagram_url',

        'facebook_url',

        'tiktok_url',

        'youtube_url',

        'whatsapp_url',

    ];
}