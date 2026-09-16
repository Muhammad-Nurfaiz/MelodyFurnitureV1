<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesJntCargoWebhookTestDatabase
{
    protected function createJntCargoWebhookTestDatabase(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Admins
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->string('full_name', 100);
                $table->string('email', 100)->unique();
                $table->string('password');
                $table->string('phone_number', 15);

                $table->string('profile_photo')->nullable();

                $table->rememberToken();

                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone');

                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Vouchers
        |--------------------------------------------------------------------------
        |
        | Orders memiliki foreign key voucher_id ke tabel vouchers.
        | Fixture webhook tidak menggunakan voucher, tetapi tabel tetap
        | diperlukan agar foreign key orders dapat dibuat.
        |
        */

        if (!Schema::hasTable('vouchers')) {
            Schema::create('vouchers', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->string('code')->unique();

                $table->string('discount_type');
                $table->decimal('discount_value', 15, 2);

                $table->decimal('minimum_purchase', 15, 2)->default(0);

                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();

                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('usage_count')->default(0);

                $table->boolean('is_active')->default(true);

                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Orders
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->foreignUuid('customer_id')
                    ->constrained('customers')
                    ->cascadeOnDelete();

                $table->foreignUuid('voucher_id')
                    ->nullable()
                    ->constrained('vouchers')
                    ->nullOnDelete();

                $table->string('order_number')->unique();

                $table->string('midtrans_order_id')->unique();

                $table->string('tracking_token')->unique();

                $table->decimal('total_product_price', 15, 2);

                $table->decimal('voucher_discount_amount', 15, 2)
                    ->default(0);

                $table->decimal('original_shipping_fee', 15, 2)
                    ->default(0);

                $table->decimal('shipping_fee', 15, 2)
                    ->default(0);

                $table->decimal('total_payment', 15, 2);

                $table->json('shipping_address');

                $table->string('shipping_method');
                $table->string('courier')->nullable();
                $table->string('tracking_number')->nullable();

                $table->decimal('total_weight', 10, 2)
                    ->nullable();

                $table->string('status')->index();
                $table->string('payment_status')->index();

                $table->timestamp('payment_expired_at')->nullable();
                $table->timestamp('paid_at')->nullable();

                $table->timestamp('picked_up_at')->nullable();
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('completed_at')->nullable();

                $table->string('customer_name')->nullable();
                $table->string('customer_phone')->nullable();
                $table->string('customer_email')->nullable();

                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->foreignUuid('order_id')
                    ->unique()
                    ->constrained('orders')
                    ->cascadeOnDelete();

                $table->string('transaction_id')->nullable();
                $table->string('snap_token')->nullable();
                $table->string('payment_type')->nullable();
                $table->string('transaction_status')->default('pending');
                $table->string('fraud_status')->nullable();

                $table->decimal('gross_amount', 12, 2);

                $table->string('bank')->nullable();
                $table->string('va_number')->nullable();

                $table->timestamp('expired_at');
                $table->timestamp('paid_at')->nullable();

                $table->json('raw_response')->nullable();
                $table->json('raw_notification')->nullable();

                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Shipments
        |--------------------------------------------------------------------------
        |
        | Dibuat langsung dalam struktur final.
        | Tidak menggunakan migration recovery shipments_old.
        |
        */

        if (!Schema::hasTable('shipments')) {
            Schema::create('shipments', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->foreignUuid('order_id')
                    ->constrained('orders')
                    ->cascadeOnDelete()
                    ->unique();

                $table->string('courier');
                $table->string('service')->nullable();
                $table->string('booking_code')->nullable();

                $table->string('tracking_number')->unique();

                $table->string('status')->index();

                $table->timestamp('picked_up_at')->nullable();
                $table->timestamp('delivered_at')->nullable();

                $table->timestamp('last_tracking_sync_at')->nullable();

                $table->json('metadata')->nullable();

                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Order Status Histories
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('order_status_histories')) {
            Schema::create('order_status_histories', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->foreignUuid('order_id')
                    ->constrained('orders')
                    ->cascadeOnDelete()
                    ->index();

                $table->string('status')->index();

                $table->text('description')->nullable();

                $table->foreignUuid('admin_id')
                    ->nullable()
                    ->constrained('admins')
                    ->nullOnDelete()
                    ->index();

                $table->string('actor')
                    ->default('system')
                    ->index();

                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | J&T Cargo Webhook Events
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('jnt_cargo_webhook_events')) {
            Schema::create('jnt_cargo_webhook_events', function (Blueprint $table) {
                $table->id();

                $table->string('event_type', 50);

                $table->string('event_key', 64);

                $table->string('tracking_number')->nullable();
                $table->string('order_number')->nullable();

                $table->json('payload');

                $table->timestamp('processed_at')->nullable();

                $table->timestamps();

                $table->unique(
                    ['event_type', 'event_key'],
                    'jnt_webhook_events_type_key_unique'
                );

                $table->index(
                    'tracking_number',
                    'jnt_webhook_events_tracking_number_index'
                );

                $table->index(
                    'order_number',
                    'jnt_webhook_events_order_number_index'
                );
            });
        }
    }
}