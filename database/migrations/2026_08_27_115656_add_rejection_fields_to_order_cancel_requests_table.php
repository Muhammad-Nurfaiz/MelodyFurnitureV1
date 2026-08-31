<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_cancel_requests', function (Blueprint $table) {

            $table->foreignUuid('rejected_by')
                ->nullable()
                ->after('approved_at')
                ->constrained('admins')
                ->nullOnDelete();

            $table->timestamp('rejected_at')
                ->nullable()
                ->after('rejected_by');
        });
    }

    public function down(): void
    {
        Schema::table('order_cancel_requests', function (Blueprint $table) {

            $table->dropForeign([
                'rejected_by',
            ]);

            $table->dropColumn([
                'rejected_by',
                'rejected_at',
            ]);
        });
    }
};