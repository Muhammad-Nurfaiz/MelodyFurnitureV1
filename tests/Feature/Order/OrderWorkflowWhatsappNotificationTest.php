<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use App\Services\Order\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendWhatsappMessage;
use Tests\TestCase;

class OrderWorkflowWhatsappNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_status_workflow_creates_whatsapp_queue_for_each_customer_notification(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Create Order
        |--------------------------------------------------------------------------
        */
        Queue::fake();
        Http::fake([
            '*' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '081234567892',
            'email' => 'customer@test.local',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'customer_name' => 'Test Customer',
            'customer_phone' => '081234567892',
            'customer_email' => 'customer@test.local',

            'order_number' => 'TEST-WA-001',
            'midtrans_order_id' => 'TEST-WA-001',
            'tracking_token' => 'test-tracking-token',

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

        $workflow = app(OrderWorkflowService::class);

        /*
        |--------------------------------------------------------------------------
        | pending → paid
        |--------------------------------------------------------------------------
        */

        $workflow->changeStatus(
            order: $order,
            status: 'paid',
            description: 'Payment berhasil diterima',
        );

        $this->assertDatabaseCount(
            'whatsapp_queues',
            1
        );

        $this->assertDatabaseHas('whatsapp_queues', [
            'phone_target' => '081234567892',
            'status' => 'pending',
        ]);

        Queue::assertPushed(SendWhatsappMessage::class);
        /*
        |--------------------------------------------------------------------------
        | paid → processing
        |--------------------------------------------------------------------------
        */

        $order = $order->fresh();

        $workflow->changeStatus(
            order: $order,
            status: 'processing',
            description: 'Pesanan mulai diproses',
        );

        $this->assertDatabaseCount(
            'whatsapp_queues',
            2
        );

        /*
        |--------------------------------------------------------------------------
        | processing → picked_up
        |--------------------------------------------------------------------------
        */

        $order = $order->fresh();

        $workflow->changeStatus(
            order: $order,
            status: 'picked_up',
            description: 'Barang telah diambil kurir.',
        );

        $this->assertDatabaseCount(
            'whatsapp_queues',
            3
        );

        /*
        |--------------------------------------------------------------------------
        | picked_up → shipped
        |--------------------------------------------------------------------------
        */

        $order = $order->fresh();

        $workflow->changeStatus(
            order: $order,
            status: 'shipped',
            description: 'Barang sedang dikirim.',
        );

        $this->assertDatabaseCount(
            'whatsapp_queues',
            4
        );

        /*
        |--------------------------------------------------------------------------
        | shipped → completed
        |--------------------------------------------------------------------------
        */

        $order = $order->fresh();

        $workflow->changeStatus(
            order: $order,
            status: 'completed',
            description: 'Pesanan telah diterima customer.',
        );

        $this->assertDatabaseCount(
            'whatsapp_queues',
            5
        );

        /*
        |--------------------------------------------------------------------------
        | Final Order Status
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }
}