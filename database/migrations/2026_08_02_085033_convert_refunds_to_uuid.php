<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Get Existing Data
        |--------------------------------------------------------------------------
        */

        $refunds = DB::table('refunds')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Disable Foreign Keys
        |--------------------------------------------------------------------------
        */

        DB::statement('PRAGMA foreign_keys = OFF');

        /*
        |--------------------------------------------------------------------------
        | Create New Table
        |--------------------------------------------------------------------------
        */

        Schema::create('refunds_new', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->string('refund_number')
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Relations
            |--------------------------------------------------------------------------
            */

            $table->uuid('order_id');

            $table->uuid('payment_id');

            /*
            |--------------------------------------------------------------------------
            | Refund
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'amount',
                15,
                2
            );

            $table->string('bank_name')
                ->nullable();

            $table->string('account_name')
                ->nullable();

            $table->string('account_number')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'rejected',
            ])->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Admin
            |--------------------------------------------------------------------------
            */

            $table->uuid('processed_by')
                ->nullable();

            $table->text('notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Dates
            |--------------------------------------------------------------------------
            */

            $table->timestamp('requested_at');

            $table->timestamp('processed_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();

            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->cascadeOnDelete();

            $table->foreign('processed_by')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | Migrate Existing Data
        |--------------------------------------------------------------------------
        */

        foreach ($refunds as $refund) {

            DB::table('refunds_new')->insert([

                /*
                |------------------------------------------------------------------
                | New UUID
                |------------------------------------------------------------------
                */

                'id' => (string) Str::uuid(),

                /*
                |------------------------------------------------------------------
                | Existing UUID Relations
                |------------------------------------------------------------------
                */

                'order_id' => $refund->order_id,

                'payment_id' => $refund->payment_id,

                'processed_by' => $refund->processed_by,

                /*
                |------------------------------------------------------------------
                | Existing Refund Data
                |------------------------------------------------------------------
                */

                'refund_number' => $refund->refund_number,

                'amount' => $refund->amount,

                'bank_name' => $refund->bank_name,

                'account_name' => $refund->account_name,

                'account_number' => $refund->account_number,

                'status' => $refund->status,

                'notes' => $refund->notes,

                'requested_at' => $refund->requested_at,

                'processed_at' => $refund->processed_at,

                'completed_at' => $refund->completed_at,

                'created_at' => $refund->created_at,

                'updated_at' => $refund->updated_at,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Replace Table
        |--------------------------------------------------------------------------
        */

        Schema::drop('refunds');

        Schema::rename(
            'refunds_new',
            'refunds'
        );

        /*
        |--------------------------------------------------------------------------
        | Enable Foreign Keys
        |--------------------------------------------------------------------------
        */

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Rollback migration convert_refunds_to_uuid tidak didukung.'
        );
    }
};