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
        Schema::table('whatsapp_queues', function (Blueprint $table) {
            $table->unsignedInteger('attempts')
                ->default(0)
                ->after('status');

            $table->timestamp('sent_at')
                ->nullable()
                ->after('attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_queues', function (Blueprint $table) {
            $table->dropColumn([
                'attempts',
                'sent_at',
            ]);
        });
    }
};