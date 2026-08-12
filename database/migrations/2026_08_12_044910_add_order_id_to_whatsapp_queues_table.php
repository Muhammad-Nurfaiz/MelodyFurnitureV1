<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_queues', function (Blueprint $table) {
            $table->uuid('order_id')
                ->nullable()
                ->after('id');

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->nullOnDelete();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_queues', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropColumn('order_id');
        });
    }
};