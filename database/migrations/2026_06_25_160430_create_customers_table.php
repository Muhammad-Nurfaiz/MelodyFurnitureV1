<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->string('phone', 15)->unique()->nullable();
            $table->string('email', 100)->nullable();
            $table->string('name', 100)->nullable();

            $table->text('address_detail')->nullable();
            $table->string('destination_code', 50)->nullable();

            $table->string('guest_token')->nullable();
            $table->string('otp_code', 10)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};