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

            $table->string('dimensions', 100);

            $table->string('seat_height', 50);

            $table->string('load_capacity', 50);

            $table->text('material_details');

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
