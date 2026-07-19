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
        Schema::create('vouchers', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->string('code',50)->unique();

            $table->enum('discount_type', [
                'percentage',
                'fixed'
            ]);

            $table->decimal('discount_value',12,2);

            $table->decimal('min_order_amount',12,2)
                ->default(0);

            $table->decimal('max_discount_amount',12,2)
                ->nullable();

            $table->dateTime('start_date')
                ->nullable();

            $table->dateTime('expiry_date');

            $table->integer('usage_limit')
                ->nullable();

            $table->integer('used_count')
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
