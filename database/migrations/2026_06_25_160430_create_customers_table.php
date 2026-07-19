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
        Schema::create('customers', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->string('phone', 15)->unique();

            $table->string('email', 100);

            $table->string('name', 100);

            $table->text('address_detail');

            $table->string('destination_code', 50);

            $table->string('guest_token')->nullable();

            $table->string('otp_code', 10)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
