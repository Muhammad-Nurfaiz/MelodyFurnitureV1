<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Current Database State
        |--------------------------------------------------------------------------
        |
        | Migration sebelumnya sudah berhasil melakukan:
        |
        | shipments -> shipments_old
        |
        | sehingga:
        |
        | shipments_old = 7 existing records
        | shipments     = empty table
        |
        | Jangan melakukan rename lagi.
        |
        */

        /*
        |--------------------------------------------------------------------------
        | Ensure Old Data Exists
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasTable('shipments_old')) {
            throw new RuntimeException(
                'Tabel shipments_old tidak ditemukan. '
                . 'Migration recovery tidak dapat dilanjutkan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create Correct Shipments Table
        |--------------------------------------------------------------------------
        |
        | orders.id = UUID / VARCHAR
        |
        | Oleh karena itu:
        |
        | shipments.order_id = UUID / VARCHAR
        |
        | UNIQUE memastikan satu order hanya memiliki satu shipment.
        |
        */

        if (! Schema::hasTable('shipments')) {

            Schema::create('shipments', function (Blueprint $table) {

                $table->id();

                $table->foreignUuid('order_id')
                    ->unique()
                    ->constrained('orders')
                    ->cascadeOnDelete();

                $table->string('courier');

                $table->string('service');

                $table->string('booking_code')
                    ->nullable();

                $table->string('tracking_number')
                    ->nullable();

                $table->string('label_url')
                    ->nullable();

                $table->enum(
                    'status',
                    [
                        'waiting_pickup',
                        'ready_to_print',
                        'picked_up',
                        'in_transit',
                        'delivered',
                        'cancelled',
                    ]
                )->default('waiting_pickup');

                $table->json('metadata')
                    ->nullable();

                $table->timestamp('picked_up_at')
                    ->nullable();

                $table->timestamp('delivered_at')
                    ->nullable();

                $table->timestamp('last_tracking_sync_at')
                    ->nullable();

                $table->timestamps();

                $table->index('tracking_number');

                $table->index('booking_code');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Copy Existing Shipment Data
        |--------------------------------------------------------------------------
        */

        DB::statement('
            INSERT INTO shipments (
                id,
                order_id,
                courier,
                service,
                booking_code,
                tracking_number,
                label_url,
                status,
                metadata,
                picked_up_at,
                delivered_at,
                last_tracking_sync_at,
                created_at,
                updated_at
            )
            SELECT
                id,
                order_id,
                courier,
                service,
                booking_code,
                tracking_number,
                label_url,
                status,
                metadata,
                picked_up_at,
                delivered_at,
                last_tracking_sync_at,
                created_at,
                updated_at
            FROM shipments_old
        ');

        /*
        |--------------------------------------------------------------------------
        | Remove Temporary Table
        |--------------------------------------------------------------------------
        */

        Schema::drop('shipments_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Safety
        |--------------------------------------------------------------------------
        |
        | Karena migration ini melakukan recovery terhadap data existing,
        | rollback tidak mencoba mengembalikan order_id menjadi INTEGER.
        |
        | Jika migration perlu dibatalkan, lebih aman menghentikan proses
        | daripada mengubah kembali foreign key UUID menjadi INTEGER.
        |
        */

        throw new RuntimeException(
            'Migration fix_shipments_order_id_type tidak mendukung rollback otomatis.'
        );
    }
};