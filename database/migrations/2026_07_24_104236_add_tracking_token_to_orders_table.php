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
        Schema::table('orders', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Public Tracking Token
            |--------------------------------------------------------------------------
            |
            | Digunakan customer untuk:
            | - Tracking Order
            | - Detail Order
            | - Download Invoice
            | - Request Cancellation
            |
            */

            $table->ulid('tracking_token')
                ->unique()
                ->after('order_number');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->dropColumn('tracking_token');

        });
    }
};