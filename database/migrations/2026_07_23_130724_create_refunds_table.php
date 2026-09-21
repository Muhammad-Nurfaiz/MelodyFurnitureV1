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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignUuid('payment_id')
                ->constrained('payments')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Refund
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'amount',
                15,
                2
            );

            $table->string('bank_name')
                ->nullable();

            $table->string('account_name')
                ->nullable();

            $table->string('account_number')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status',[
                'pending',
                'processing',
                'completed',
                'rejected',
            ])->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Admin
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid('processed_by')
                ->nullable()
                ->constrained('admins')
                ->nullOnDelete();

            $table->text('notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            $table->timestamp('requested_at');

            $table->timestamp('processed_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
