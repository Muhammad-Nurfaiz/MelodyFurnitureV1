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

            /*
            |--------------------------------------------------------------------------
            | Relationship
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid('customer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('voucher_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            $table->string('order_number')
                ->unique();

            $table->string('midtrans_order_id')
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            $table->decimal('total_product_price', 12, 2);

            $table->decimal('voucher_discount_amount', 12, 2)
                ->default(0);

            $table->decimal('original_shipping_fee', 12, 2)
                ->default(0);

            $table->decimal('shipping_fee', 12, 2)
                ->default(0);

            $table->decimal('total_payment', 12, 2);

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            $table->json('shipping_address');

            $table->string('shipping_method');

            $table->string('courier')
                ->nullable();

            $table->string('tracking_number')
                ->nullable();

            $table->unsignedInteger('total_weight')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Order Status
            |--------------------------------------------------------------------------
            */

            $table->string('status')
                ->default('pending');

            $table->string('payment_status')
                ->default('unpaid');

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            $table->timestamp('payment_expired_at');

            $table->timestamp('paid_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timeline
            |--------------------------------------------------------------------------
            */

            $table->timestamp('packed_at')
                ->nullable();

            $table->timestamp('picked_up_at')
                ->nullable();

            $table->timestamp('shipped_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamp('cancelled_at')
                ->nullable();

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