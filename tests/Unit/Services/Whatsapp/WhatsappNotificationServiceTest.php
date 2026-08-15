<?php

namespace Tests\Unit\Services\Whatsapp;

use App\Models\Order;
use App\Models\Customer;
use App\Models\WhatsappQueue;
use App\Services\Whatsapp\WhatsappMessageTemplateService;
use App\Services\Whatsapp\WhatsappNotificationService;
use App\Jobs\SendWhatsappMessage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.local',
            'phone' => '081234567892',
        ]);
    }

    protected function makeOrder(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'customer_id' => $this->customer->id,
            'customer_name' => 'Test Customer',
            'customer_phone' => '081234567892',
            'customer_email' => 'customer@test.local',
            'order_number' => 'ORDER-TEST-0001',
            'midtrans_order_id' => 'MIDTRANS-TEST-0001',
            'tracking_token' => 'TRACKING-TEST-0001',

            'total_product_price' => 1_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 25_000,
            'shipping_fee' => 25_000,
            'total_payment' => 1_025_000,

            'shipping_address' => [
                'recipient_name' => 'Test Customer',
                'phone' => '081234567892',
                'address' => 'Jl. Test No. 1',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65100',
            ],

            'shipping_method' => 'jne',
            'courier' => 'JNE',
            'tracking_number' => null,
            'total_weight' => 10,

            'status' => 'paid',
            'payment_status' => 'paid',

            'payment_expired_at' => now()->addMinutes(30),
            'paid_at' => now(),

            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ], $attributes));
    }

    public function test_paid_order_notification_is_added_to_queue(): void
    {
        $order = $this->makeOrder([
            'status' => 'paid',
        ]);

        $service = app(WhatsappNotificationService::class);

        $queue = $service->queueOrderStatusNotification(
            order: $order,
            status: 'paid',
        );

        $this->assertInstanceOf(
            WhatsappQueue::class,
            $queue
        );

        $this->assertSame(
            $order->customer_phone,
            $queue->phone_target
        );

        $this->assertSame(
            'pending',
            $queue->status
        );

        $this->assertNotEmpty(
            $queue->message_text
        );

        $this->assertDatabaseHas('whatsapp_queues', [
            'id' => $queue->id,
            'phone_target' => $order->customer_phone,
            'status' => 'pending',
        ]);
    }

    public function test_processing_order_notification_is_added_to_queue(): void
    {
        $order = $this->makeOrder([
            'status' => 'processing',
        ]);

        $service = app(WhatsappNotificationService::class);

        $queue = $service->queueOrderStatusNotification(
            order: $order,
            status: 'processing',
        );

        $this->assertNotNull($queue);

        $this->assertDatabaseHas('whatsapp_queues', [
            'id' => $queue->id,
            'phone_target' => $order->customer_phone,
            'status' => 'pending',
        ]);
    }

    public function test_shipped_order_notification_is_added_to_queue(): void
    {
        $order = $this->makeOrder([
            'status' => 'shipped',
            'tracking_number' => 'JNE123456789',
        ]);

        $service = app(WhatsappNotificationService::class);

        $queue = $service->queueOrderStatusNotification(
            order: $order,
            status: 'shipped',
        );

        $this->assertNotNull($queue);

        $this->assertStringContainsString(
            'JNE123456789',
            $queue->message_text
        );
    }

    public function test_unknown_status_does_not_create_queue(): void
    {
        $order = $this->makeOrder([
            'customer_phone' => null,
        ]);

        $service = app(WhatsappNotificationService::class);

        $queue = $service->queueOrderStatusNotification(
            order: $order,
            status: 'unknown_status',
        );

        $this->assertNull($queue);

        $this->assertDatabaseCount(
            'whatsapp_queues',
            0
        );
    }

    public function test_order_without_customer_phone_does_not_create_queue(): void
    {
        $order = $this->makeOrder([
            'customer_phone' => null,
        ]);

        $service = app(WhatsappNotificationService::class);

        $queue = $service->queueOrderStatusNotification(
            order: $order,
            status: 'paid',
        );

        $this->assertNull($queue);

        $this->assertDatabaseCount(
            'whatsapp_queues',
            0
        );
    }

    public function test_failed_whatsapp_queue_can_be_retried(): void
    {
        Queue::fake();

        $queue = WhatsappQueue::create([
            'phone_target' => '081234567892',
            'message_text' => 'Pesan WhatsApp gagal',
            'status' => 'failed',
            'error_log' => 'WAHA connection failed',
            'sent_at' => now()->subMinute(),
        ]);

        $service = app(WhatsappNotificationService::class);

        $service->retry($queue);

        $this->assertDatabaseHas('whatsapp_queues', [
            'id' => $queue->id,
            'status' => 'pending',
            'error_log' => null,
            'sent_at' => null,
        ]);

        Queue::assertPushed(
            SendWhatsappMessage::class,
            function (SendWhatsappMessage $job) use ($queue) {
                return $job->whatsappQueueId === $queue->id;
            }
        );
    }

    public function test_non_failed_whatsapp_queue_cannot_be_retried(): void
    {
        Queue::fake();

        $queue = WhatsappQueue::create([
            'phone_target' => '081234567892',
            'message_text' => 'Pesan WhatsApp pending',
            'status' => 'pending',
            'error_log' => null,
            'sent_at' => null,
        ]);

        $service = app(WhatsappNotificationService::class);

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage(
            'Pesan WhatsApp hanya dapat di-retry jika statusnya failed.'
        );

        $service->retry($queue);

        Queue::assertNothingPushed();
    }
}