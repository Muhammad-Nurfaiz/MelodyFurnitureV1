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
        Schema::create('orders', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('voucher_id')
                ->nullable()
                ->constrained('vouchers')
                ->nullOnDelete();

            $table->foreignUuid('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->string('order_number')
                ->unique();

            $table->decimal('total_product_price',12,2);

            $table->decimal('voucher_discount_amount',12,2)
                ->default(0);

            $table->decimal('original_shipping_fee',12,2)
                ->default(0);

            $table->decimal('shipping_fee',12,2);

            $table->decimal('total_payment',12,2);

            $table->string('shipping_method');

            $table->text('shipping_address')
                ->nullable();

            $table->enum('status',[
                'pending',
                'packed',
                'picked_up',
                'transit',
                'delivered',
                'cancelled'
            ])->default('pending');

            $table->dateTime('payment_expired_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
