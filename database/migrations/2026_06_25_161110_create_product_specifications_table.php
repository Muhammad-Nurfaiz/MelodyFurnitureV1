<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')
                ->unique()
                ->constrained('products')
                ->cascadeOnDelete();
            /*
            |--------------------------------------------------------------------------
            | Physical Specification
            |--------------------------------------------------------------------------
            */
            // Contoh: 60 x 55 x 80 cm
            $table->string('dimensions', 100);
            // Berat asli produk (kg)
            $table->decimal('weight', 8, 2);
            // Berat setelah packing (kg)
            $table->decimal('packing_weight', 8, 2);
            // Contoh: 120 kg
            $table->string('load_capacity', 50);
            /*
            |--------------------------------------------------------------------------
            | Material
            |--------------------------------------------------------------------------
            */
            $table->text('material_details');
            /*
            |--------------------------------------------------------------------------
            | Assembly
            |--------------------------------------------------------------------------
            */
            $table->boolean('assembly_required')
                ->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specifications');
    }
};
