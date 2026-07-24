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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('courier');

            $table->string('service');

            $table->string('booking_code')
                ->nullable();

            $table->string('tracking_number')
                ->nullable();

            $table->string('label_url')
                ->nullable();

            $table->enum(
                'status',
                [
                    'waiting_pickup',
                    'ready_to_print',
                    'picked_up',
                    'in_transit',
                    'delivered',
                    'cancelled',
                ]
            )->default('waiting_pickup');

            $table->json('metadata')
                ->nullable();

            $table->timestamp('picked_up_at')
                ->nullable();

            $table->timestamp('delivered_at')
                ->nullable();

            $table->timestamps();

            $table->index('tracking_number');
            $table->index('booking_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
