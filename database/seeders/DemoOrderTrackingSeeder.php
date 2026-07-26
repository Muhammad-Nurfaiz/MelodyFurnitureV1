<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoOrderTrackingSeeder extends Seeder
{
    public function run(): void
    {
        $customer = Customer::updateOrCreate(
            [
                'phone' => '081234567890',
            ],
            [
                'email' => 'test@melodyfurniture.test',
                'name' => 'Test Customer',
                'address_detail' => 'Jl. Test No. 1, Lowokwaru, Malang',
                'destination_code' => 'KOTA_MALANG',
                'guest_token' => (string) Str::ulid(),
                'otp_code' => null,
            ]
        );

        $order = Order::updateOrCreate(
            [
                'order_number' => 'ORD-DEMO-0001',
            ],
            [
                'customer_id' => $customer->id,
                'voucher_id' => null,
                'midtrans_order_id' => 'TEST-' . strtoupper(Str::random(10)),

                'total_product_price' => 1500000,
                'voucher_discount_amount' => 0,
                'original_shipping_fee' => 50000,
                'shipping_fee' => 50000,
                'total_payment' => 1550000,

                'shipping_address' => [
                    'recipient_name' => $customer->name,
                    'phone' => $customer->phone,
                    'province' => 'East Java',
                    'city' => 'Malang',
                    'district' => 'Lowokwaru',
                    'postal_code' => '65141',
                    'address' => 'Jl. Test No. 1',
                ],

                'shipping_method' => 'delivery',
                'courier' => 'jnt_cargo',
                'tracking_number' => null,
                'total_weight' => 10,

                'status' => 'pending',
                'payment_status' => 'pending',
                'tracking_token' => (string) Str::ulid(),

                'payment_expired_at' => now()->addMinutes(10),
                'paid_at' => null,
                'packed_at' => null,
                'picked_up_at' => null,
                'shipped_at' => null,
                'completed_at' => null,
                'cancelled_at' => null,
            ]
        );

        OrderStatusHistory::updateOrCreate(
            [
                'order_id' => $order->id,
                'status' => 'pending',
            ],
            [
                'description' => 'Pesanan berhasil dibuat.',
                'created_by' => null,
            ]
        );
    }
}