<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {

            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | Content
            |--------------------------------------------------------------------------
            */

            $table->string('image');

            $table->string('eyebrow', 100)->nullable();

            $table->string('title', 200);

            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | CTA
            |--------------------------------------------------------------------------
            */

            $table->string('button_text', 100)->nullable();

            $table->string('button_url')->nullable();

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
        Schema::dropIfExists('hero_slides');
    }
};