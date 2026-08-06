<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {

            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | Store Identity
            |--------------------------------------------------------------------------
            */

            $table->string('store_name', 150);

            $table->text('store_description')->nullable();

            $table->string('store_logo')->nullable();

            $table->string('store_favicon')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Social Media
            |--------------------------------------------------------------------------
            */

            $table->string('instagram_url')->nullable();

            $table->string('facebook_url')->nullable();

            $table->string('tiktok_url')->nullable();

            $table->string('youtube_url')->nullable();

            $table->string('whatsapp_url')->nullable();

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
        Schema::dropIfExists('settings');
    }
};