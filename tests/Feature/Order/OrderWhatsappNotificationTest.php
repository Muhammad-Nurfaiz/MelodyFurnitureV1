<?php

namespace Tests\Feature\Order;

use App\Events\OrderStatusChanged;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Jobs\SendWhatsappMessage;
use Illuminate\Support\Facades\Queue;

class OrderWhatsappNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_status_changed_creates_whatsapp_queue(): void
    {
        Queue::fake();
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

            'order_number' => 'ORDER-TEST-0001',
            'midtrans_order_id' => 'MIDTRANS-TEST-0001',
            'tracking_token' => 'TRACKING-TEST-0001',

            'total_product_price' => 1000000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 25000,
            'shipping_fee' => 25000,
            'total_payment' => 1025000,

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

            'status' => 'pending',
            'payment_status' => 'pending',

            'payment_expired_at' => now()->addMinutes(30),
            'paid_at' => null,
            'packed_at' => null,
            'picked_up_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        $order->update([
            'status' => 'paid',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        OrderStatusChanged::dispatch(
            order: $order->fresh(),
            previousStatus: 'pending',
            newStatus: 'paid',
        );

        $this->assertDatabaseHas('whatsapp_queues', [
            'phone_target' => '081234567892',
            'status' => 'pending',
        ]);
    }
}