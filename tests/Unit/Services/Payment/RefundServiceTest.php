<?php

namespace Tests\Unit\Services\Payment;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\Payment\PaymentService;
use App\Services\Payment\RefundNumberService;
use App\Services\Payment\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;
use Illuminate\Support\Str;

class RefundServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RefundService $refundService;
    protected Admin $admin;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refundService = app(RefundService::class);

        $this->admin = Admin::create([
            'full_name' => 'Test Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'phone_number' => '081234567890',
        ]);

        $this->customer = Customer::create([
            'phone' => '081234567891',
            'email' => 'customer@test.local',
            'name' => 'Test Customer',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function createOrder(
        float $totalPayment = 1775000
    ): Order {
        return Order::create([
            'customer_id' => $this->customer->id,
            'voucher_id' => null,

            'order_number' => 'TEST-' . uniqid(),
            'midtrans_order_id' => 'MID-' . uniqid(),

            'total_product_price' => $totalPayment - 25000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 25000,
            'shipping_fee' => 25000,
            'total_payment' => $totalPayment,

            'shipping_address' => json_encode([
                'recipient_name' => 'Test Customer',
                'phone' => '081234567891',
                'address' => 'Jl. Test No. 1',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65141',
            ]),

            'shipping_method' => 'REG',
            'courier' => 'jne',
            'tracking_number' => null,
            'tracking_token' => (string) Str::ulid(),
            'total_weight' => 1,

            'status' => 'pending',
            'payment_status' => 'pending',

            'payment_expired_at' => now()->addMinutes(10),
        ]);
    }

    protected function createPayment(
        Order $order,
        string $status = 'settlement',
        ?float $grossAmount = null
    ): Payment {
        return Payment::create([
            'order_id' => $order->id,

            'transaction_id' => 'TRX-' . uniqid(),
            'snap_token' => 'SNAP-' . uniqid(),
            'payment_type' => 'bank_transfer',

            'transaction_status' => $status,
            'fraud_status' => 'accept',

            'gross_amount' => $grossAmount ?? $order->total_payment,

            'bank' => 'bca',
            'va_number' => '123456789',

            'expired_at' => now()->addMinutes(10),

            'paid_at' => in_array(
                $status,
                ['capture', 'settlement'],
                true
            ) ? now() : null,

            'raw_response' => [],
        ]);
    }

    protected function createRefund(
        Order $order,
        Payment $payment,
        float $amount = 1775000,
        string $status = 'pending'
    ): Refund {
        return Refund::create([
            'refund_number' => app(RefundNumberService::class)->generate(),

            'order_id' => $order->id,
            'payment_id' => $payment->id,

            'amount' => $amount,

            'status' => $status,

            'requested_at' => now(),

            'processed_by' => null,
            'processed_at' => null,
            'completed_at' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function test_create_creates_pending_refund(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->refundService->create($order);

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => '1775000.00',
            'status' => 'pending',
        ]);

        $this->assertTrue($refund->isPending());
        $this->assertNotNull($refund->refund_number);
        $this->assertNotNull($refund->requested_at);
    }

    public function test_create_rejects_duplicate_refund(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $this->createRefund($order, $payment);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Refund sudah pernah dibuat untuk order ini.'
        );

        $this->refundService->create($order);
    }

    public function test_create_rejects_order_without_payment(): void
    {
        $order = $this->createOrder();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Order belum memiliki payment.'
        );

        $this->refundService->create($order);
    }

    public function test_create_rejects_unpaid_payment(): void
    {
        $order = $this->createOrder();

        $this->createPayment(
            $order,
            'pending'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Payment belum berada pada status yang dapat direfund.'
        );

        $this->refundService->create($order);
    }

    public function test_create_rejects_invalid_amount(): void
    {
        $order = $this->createOrder(0);
        $this->createPayment($order, 'settlement', 0);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Jumlah refund tidak valid.'
        );

        $this->refundService->create($order);
    }

    public function test_create_rejects_amount_greater_than_payment(): void
    {
        $order = $this->createOrder(2000000);

        $this->createPayment(
            $order,
            'settlement',
            1000000
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Jumlah refund melebihi jumlah payment.'
        );

        $this->refundService->create($order);
    }

    /*
    |--------------------------------------------------------------------------
    | START
    |--------------------------------------------------------------------------
    */

    public function test_start_moves_pending_refund_to_processing(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment
        );

        $result = $this->refundService->start(
            $refund,
            $this->admin
        );

        $this->assertSame(
            'processing',
            $result->status
        );

        $this->assertSame(
            $this->admin->id,
            $result->processed_by
        );

        $this->assertNotNull(
            $result->processed_at
        );
    }

    public function test_start_rejects_non_pending_refund(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment,
            1775000,
            'rejected'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Hanya refund dengan status pending yang dapat diproses.'
        );

        $this->refundService->start(
            $refund,
            $this->admin
        );
    }

    public function test_start_rejects_unpaid_payment(): void
    {
        $order = $this->createOrder();

        $payment = $this->createPayment(
            $order,
            'pending'
        );

        $refund = $this->createRefund(
            $order,
            $payment
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Payment tidak berada pada status yang dapat direfund.'
        );

        $this->refundService->start(
            $refund,
            $this->admin
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT
    |--------------------------------------------------------------------------
    */

    public function test_reject_moves_pending_refund_to_rejected(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment
        );

        $result = $this->refundService->reject(
            $refund,
            $this->admin,
            '  Refund ditolak untuk testing.  '
        );

        $this->assertSame(
            'rejected',
            $result->status
        );

        $this->assertSame(
            $this->admin->id,
            $result->processed_by
        );

        $this->assertSame(
            'Refund ditolak untuk testing.',
            $result->notes
        );

        $this->assertNotNull(
            $result->processed_at
        );

        $this->assertNull(
            $result->completed_at
        );

        $this->assertSame(
            'settlement',
            $payment->fresh()->transaction_status
        );
    }

    public function test_reject_requires_reason(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Alasan penolakan refund wajib diisi.'
        );

        $this->refundService->reject(
            $refund,
            $this->admin,
            '   '
        );
    }

    public function test_reject_rejects_non_pending_refund(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment,
            1775000,
            'completed'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Refund hanya dapat ditolak dari status pending.'
        );

        $this->refundService->reject(
            $refund,
            $this->admin,
            'Percobaan reject.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE
    |--------------------------------------------------------------------------
    */

    public function test_complete_moves_processing_refund_to_completed(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment
        );

        $refund = $this->refundService->start(
            $refund,
            $this->admin
        );

        $result = $this->refundService->complete(
            $refund,
            $this->admin,
            'Dana telah dikembalikan kepada customer.'
        );

        $this->assertSame(
            'completed',
            $result->status
        );

        $this->assertSame(
            $this->admin->id,
            $result->processed_by
        );

        $this->assertSame(
            'Dana telah dikembalikan kepada customer.',
            $result->notes
        );

        $this->assertNotNull(
            $result->completed_at
        );

        $this->assertSame(
            'refunded',
            $payment->fresh()->transaction_status
        );
    }

    public function test_complete_rejects_non_processing_refund(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Refund hanya dapat diselesaikan dari status processing.'
        );

        $this->refundService->complete(
            $refund,
            $this->admin,
            'Percobaan complete.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TERMINAL STATE
    |--------------------------------------------------------------------------
    */

    public function test_rejected_refund_cannot_be_completed(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment,
            1775000,
            'rejected'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Refund hanya dapat diselesaikan dari status processing.'
        );

        $this->refundService->complete(
            $refund,
            $this->admin
        );
    }

    public function test_rejected_refund_cannot_be_rejected_again(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment,
            1775000,
            'rejected'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Refund hanya dapat ditolak dari status pending.'
        );

        $this->refundService->reject(
            $refund,
            $this->admin,
            'Percobaan reject kedua.'
        );
    }

    public function test_rejected_refund_cannot_be_started(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment,
            1775000,
            'rejected'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Hanya refund dengan status pending yang dapat diproses.'
        );

        $this->refundService->start(
            $refund,
            $this->admin
        );
    }

    public function test_completed_refund_cannot_be_started(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment,
            1775000,
            'completed'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Hanya refund dengan status pending yang dapat diproses.'
        );

        $this->refundService->start(
            $refund,
            $this->admin
        );
    }

    public function test_completed_refund_cannot_be_rejected(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment,
            1775000,
            'completed'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Refund hanya dapat ditolak dari status pending.'
        );

        $this->refundService->reject(
            $refund,
            $this->admin,
            'Percobaan reject.'
        );
    }

    public function test_completed_refund_cannot_be_completed_again(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $refund = $this->createRefund(
            $order,
            $payment,
            1775000,
            'completed'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Refund hanya dapat diselesaikan dari status processing.'
        );

        $this->refundService->complete(
            $refund,
            $this->admin
        );
    }
}