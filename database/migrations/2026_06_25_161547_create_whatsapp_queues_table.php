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
        Schema::create('whatsapp_queues', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->string('phone_target',15);

            $table->text('message_text');

            $table->enum('status',[
                'pending',
                'processing',
                'success',
                'failed'
            ])->default('pending');

            $table->text('error_log')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_queues');
    }
};
