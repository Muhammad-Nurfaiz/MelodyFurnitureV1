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
        Schema::create('shipping_rates', function (Blueprint $table) {

            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | Relations
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid('courier_id')
                ->constrained('shipping_couriers')
                ->cascadeOnDelete();

            $table->string('regency_id', 10);

            $table->foreign('regency_id')
                ->references('id')
                ->on('regencies')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Rate Configuration
            |--------------------------------------------------------------------------
            */

            $table->enum('rate_type', [
                'per_kg',
                'tiered',
            ]);

            /*
            | J&T Cargo
            | Tarif langsung per kilogram.
            */
            $table->decimal('price_per_kg', 12, 2)
                ->nullable();

            /*
            | Sentral Cargo
            | Tarif dasar untuk berat 1–10 kg.
            */
            $table->decimal('first_price', 12, 2)
                ->nullable();

            /*
            | Sentral Cargo
            | Tarif tambahan untuk setiap kg berikutnya.
            */
            $table->decimal('additional_price_per_kg', 12, 2)
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Constraints
            |--------------------------------------------------------------------------
            */

            $table->unique([
                'courier_id',
                'regency_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};