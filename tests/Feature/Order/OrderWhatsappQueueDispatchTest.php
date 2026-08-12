<?php

namespace Tests\Feature\Order;

use App\Events\OrderStatusChanged;
use App\Listeners\SendOrderWhatsappNotification;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderWhatsappQueueDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_job_is_dispatched_to_database_queue(): void
    {
        Queue::fake();

        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '081234567892',
            'email' => 'customer@test.local',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'order_number' => 'TEST-QUEUE-001',
            'midtrans_order_id' => 'TEST-QUEUE-001',
            'tracking_token' => 'test-queue-token',

            'total_product_price' => 100000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 10000,
            'shipping_fee' => 10000,
            'total_payment' => 110000,
            'total_weight' => 1000,

            'shipping_method' => 'regular',
            'courier' => 'jne',

            'shipping_address' => [
                'name' => 'Test Customer',
                'phone' => '081234567892',
                'address' => 'Test Address',
            ],

            'status' => 'pending',
            'payment_status' => 'pending',

            'payment_expired_at' => now()->addHour(),
        ]);

        $event = new OrderStatusChanged(
            order: $order,
            previousStatus: 'pending',
            newStatus: 'paid',
        );

        app(SendOrderWhatsappNotification::class)
            ->handle($event);

        $this->assertDatabaseHas('whatsapp_queues', [
            'phone_target' => '081234567892',
            'status' => 'pending',
        ]);

        Queue::assertPushed(
            \App\Jobs\SendWhatsappMessage::class
        );
    }
}