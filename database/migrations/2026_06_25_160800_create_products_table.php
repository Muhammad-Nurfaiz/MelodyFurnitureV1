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
        Schema::create('products', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->string('jubelio_variant_id',100)->nullable();

            $table->foreignUuid('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('series_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name',150);

            $table->string('slug')->unique();

            $table->text('description');

            $table->longText('product_detail')->nullable();

            $table->decimal('original_price',12,2);

            $table->decimal('discount_price',12,2)->nullable();

            $table->unsignedTinyInteger('discount_percentage')->nullable();

            $table->boolean('is_sale')->default(false);

            $table->unsignedInteger('ready_stock')->default(0);

            $table->unsignedInteger('locked_stock')->default(0);

            $table->text('video_tutorial_url')->nullable();

            $table->string('origin_city',50)->nullable();

            $table->decimal('average_rating',2,1)->default(0);

            $table->unsignedInteger('total_sold')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
