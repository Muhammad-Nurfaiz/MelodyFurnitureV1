<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jnt_cargo_webhook_events', function (Blueprint $table) {
            $table->id();

            $table->string('event_type', 50);

            $table->string('event_key', 64);

            $table->string('tracking_number')->nullable();
            $table->string('order_number')->nullable();

            $table->json('payload');

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['event_type', 'event_key'],
                'jnt_webhook_events_type_key_unique'
            );

            $table->index(
                'tracking_number',
                'jnt_webhook_events_tracking_number_index'
            );

            $table->index(
                'order_number',
                'jnt_webhook_events_order_number_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jnt_cargo_webhook_events');
    }
};