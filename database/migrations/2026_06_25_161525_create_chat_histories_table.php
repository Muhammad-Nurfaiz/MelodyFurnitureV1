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
        Schema::create('chat_histories', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->string('session_token');

            $table->foreignUuid('admin_id')
                ->nullable()
                ->constrained('admins')
                ->nullOnDelete();

            $table->text('message');

            $table->enum('sender',[
                'customer_guest',
                'system',
                'admin'
            ]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_histories');
    }
};
