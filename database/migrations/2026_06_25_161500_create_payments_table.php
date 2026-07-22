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
        Schema::create('payments', function (Blueprint $table) {

            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | Relation
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid('order_id')
                ->unique()
                ->constrained('orders')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Midtrans
            |--------------------------------------------------------------------------
            */

            $table->string('transaction_id')
                ->nullable();

            $table->string('snap_token')
                ->nullable();

            $table->string('payment_type')
                ->nullable();

            $table->string('transaction_status')
                ->default('pending');

            $table->string('fraud_status')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Payment Detail
            |--------------------------------------------------------------------------
            */

            $table->decimal('gross_amount', 12, 2);

            $table->string('bank')
                ->nullable();

            $table->string('va_number')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Time
            |--------------------------------------------------------------------------
            */

            $table->timestamp('expired_at');

            $table->timestamp('paid_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Raw Callback Midtrans
            |--------------------------------------------------------------------------
            */

            $table->json('raw_response')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};