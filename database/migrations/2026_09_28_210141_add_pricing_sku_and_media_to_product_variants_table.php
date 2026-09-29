<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('sku', 100)
                ->nullable()
                ->after('name');

            $table->decimal('original_price', 12, 2)
                ->nullable()
                ->after('sku');

            $table->decimal('discount_price', 12, 2)
                ->nullable()
                ->after('original_price');

            $table->unsignedTinyInteger('discount_percentage')
                ->nullable()
                ->after('discount_price');

            $table->foreignUuid('media_id')
                ->nullable()
                ->after('discount_percentage')
                ->constrained('product_media')
                ->nullOnDelete();
        });

        DB::statement('
            UPDATE product_variants
            SET
                original_price = (
                    SELECT products.original_price
                    FROM products
                    WHERE products.id = product_variants.product_id
                ),
                discount_price = (
                    SELECT products.discount_price
                    FROM products
                    WHERE products.id = product_variants.product_id
                ),
                discount_percentage = (
                    SELECT products.discount_percentage
                    FROM products
                    WHERE products.id = product_variants.product_id
                )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign(['media_id']);
            $table->dropColumn([
                'sku',
                'original_price',
                'discount_price',
                'discount_percentage',
                'media_id',
            ]);
        });
    }
};