<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            DB::statement('ALTER TABLE cart_items DROP INDEX cart_items_cart_id_product_id_unique');
        } catch (\Exception $e) {
            // Lanjutkan jika indeks sudah terhapus sebelumnya
        }

        try {
            DB::statement('
                ALTER TABLE cart_items 
                ADD UNIQUE KEY cart_items_cart_product_variant_unique (cart_id, product_id, product_variant_id)
            ');
        } catch (\Exception $e) {
            // Lanjutkan jika unique key sudah ada
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            DB::statement('ALTER TABLE cart_items DROP INDEX cart_items_cart_product_variant_unique');
        } catch (\Exception $e) {}

        try {
            DB::statement('
                ALTER TABLE cart_items 
                ADD UNIQUE KEY cart_items_cart_id_product_id_unique (cart_id, product_id)
            ');
        } catch (\Exception $e) {}

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};