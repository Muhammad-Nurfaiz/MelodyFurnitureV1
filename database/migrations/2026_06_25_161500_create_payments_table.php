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

            $table->foreignUuid('order_id')
                ->unique()
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->string('target_bank',50);

            $table->string('va_number',50);

            $table->dateTime('expiry_time');

            $table->enum('status',[
                'awaiting_payment',
                'paid',
                'expired'
            ])->default('awaiting_payment');

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
