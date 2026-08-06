<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_banners', function (Blueprint $table) {

            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | Banner Image
            |--------------------------------------------------------------------------
            */

            $table->string('image');

            /*
            |--------------------------------------------------------------------------
            | Accessibility
            |--------------------------------------------------------------------------
            */

            $table->string('url', 255)->nullable();

            $table->string('alt', 255)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Display
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('sort_order')->default(0);

            $table->boolean('is_active')->default(true);

            /*
            |--------------------------------------------------------------------------
            | Timestamp
            |--------------------------------------------------------------------------
            */

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_banners');
    }
};