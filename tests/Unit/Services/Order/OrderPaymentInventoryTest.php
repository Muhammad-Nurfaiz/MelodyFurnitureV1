<?php

namespace Tests\Unit\Services\Order;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Payment;
use App\Services\Admin\AdminNotificationService;
use App\Services\Cart\CartService;
use App\Services\Inventory\ProductInventoryService;
use App\Services\Order\OrderCalculatorService;
use App\Services\Order\OrderNumberService;
use App\Services\Order\OrderService;
use App\Services\Order\OrderTrackingTokenService;
use App\Services\Order\OrderWorkflowService;
use App\Services\Order\OrderCancellationService;
use App\Services\Payment\RefundService;
use App\Services\Payment\MidtransService;
use App\Services\Payment\PaymentService;
use App\Services\Voucher\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class OrderPaymentInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_paid_moves_locked_stock_to_sold_without_increasing_ready_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Payment Test',
            'slug' => 'furniture-payment-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Payment Test',
            'slug' => 'produk-payment-test',
            'description' => 'Produk untuk pengujian inventory payment.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 7,
            'locked_stock' => 3,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567890',
            'email' => 'payment-inventory@test.test',
            'name' => 'Payment Inventory Customer',
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-PAYMENT-INVENTORY-TEST',
            'midtrans_order_id' => 'MIDTRANS-PAYMENT-INVENTORY-TEST',
            'tracking_token' => (string) Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 3_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 3_000_000,

            'shipping_address' => [
                'address' => 'Alamat Payment Inventory Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 3,

            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_expired_at' => now()->addHours(24),
            'paid_at' => null,
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 3,
            'unit_price' => 1_000_000,
            'subtotal' => 3_000_000,
        ]);

        $order->load('items.product');

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(null);

        $workflowService
            ->shouldReceive('changeStatus')
            ->once()
            ->andReturnUsing(
                function (
                    Order $order,
                    string $status,
                    string $description,
                    ?string $adminId = null,
                ) {
                    $order->status = $status;
                    $order->save();

                    return $order;
                }
            );

        $orderService = new OrderService(
            paymentService: Mockery::mock(PaymentService::class),
            midtransService: Mockery::mock(MidtransService::class),
            inventoryService: app(ProductInventoryService::class),
            voucherService: app(VoucherService::class),
            numberService: app(OrderNumberService::class),
            workflowService: $workflowService,
            calculatorService: app(OrderCalculatorService::class),
            trackingTokenService: app(OrderTrackingTokenService::class),
            cartService: app(CartService::class),
            adminNotificationService: Mockery::mock(
                AdminNotificationService::class
            ),
        );

        $orderService->markPaid($order);

        $product->refresh();

        $this->assertSame(7, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);

        $order->refresh();

        $this->assertSame('paid', $order->status);
        $this->assertSame('paid', $order->payment_status);
    }

    public function test_mark_paid_moves_variant_locked_stock_to_sold_without_increasing_ready_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Variant Payment Test',
            'slug' => 'furniture-variant-payment-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Variant Payment Test',
            'slug' => 'produk-variant-payment-test',
            'description' => 'Produk variant untuk pengujian inventory payment.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,

            // Produk dengan variant tidak menggunakan stok parent.
            'ready_stock' => 0,
            'locked_stock' => 0,
        ]);

        $variant = ProductVariant::create([
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 7,
            'locked_stock' => 3,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567891',
            'email' => 'variant-payment-inventory@test.test',
            'name' => 'Variant Payment Inventory Customer',
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-VARIANT-PAYMENT-TEST',
            'midtrans_order_id' => 'MIDTRANS-VARIANT-PAYMENT-TEST',
            'tracking_token' => (string) Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 3_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 3_000_000,

            'shipping_address' => [
                'address' => 'Alamat Variant Payment Inventory Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 3,

            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_expired_at' => now()->addHours(24),
            'paid_at' => null,
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_variant_name' => $variant->name,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 3,
            'unit_price' => 1_000_000,
            'subtotal' => 3_000_000,
        ]);

        $order->load('items.product');

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('validate')
            ->once()
            ->andReturn(null);

        $workflowService
            ->shouldReceive('changeStatus')
            ->once()
            ->andReturnUsing(
                function (
                    Order $order,
                    string $status,
                    string $description,
                    ?string $adminId = null,
                ) {
                    $order->status = $status;
                    $order->save();

                    return $order;
                }
            );

        $orderService = new OrderService(
            paymentService: Mockery::mock(PaymentService::class),
            midtransService: Mockery::mock(MidtransService::class),
            inventoryService: app(ProductInventoryService::class),
            voucherService: app(VoucherService::class),
            numberService: app(OrderNumberService::class),
            workflowService: $workflowService,
            calculatorService: app(OrderCalculatorService::class),
            trackingTokenService: app(OrderTrackingTokenService::class),
            cartService: app(CartService::class),
            adminNotificationService: Mockery::mock(
                AdminNotificationService::class
            ),
        );

        $orderService->markPaid($order);

        $variant->refresh();
        $product->refresh();

        // Stok ready variant tidak bertambah ketika pembayaran dikonfirmasi.
        $this->assertSame(7, $variant->ready_stock);

        // Locked stock variant berkurang sesuai quantity terjual.
        $this->assertSame(0, $variant->locked_stock);

        // Stok parent product tetap tidak berubah.
        $this->assertSame(0, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);
    }

    public function test_cancel_pending_order_releases_variant_locked_stock_back_to_ready_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Variant Cancel Test',
            'slug' => 'furniture-variant-cancel-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Variant Cancel Test',
            'slug' => 'produk-variant-cancel-test',
            'description' => 'Produk variant untuk pengujian inventory cancel.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,

            // Stok parent tidak digunakan ketika produk memiliki variant.
            'ready_stock' => 0,
            'locked_stock' => 0,
        ]);

        $variant = ProductVariant::create([
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 4,
            'locked_stock' => 3,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567892',
            'email' => 'variant-cancel-inventory@test.test',
            'name' => 'Variant Cancel Inventory Customer',
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-VARIANT-CANCEL-TEST',
            'midtrans_order_id' => 'MIDTRANS-VARIANT-CANCEL-TEST',
            'tracking_token' => (string) Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 3_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 3_000_000,

            'shipping_address' => [
                'address' => 'Alamat Variant Cancel Inventory Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 3,

            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_expired_at' => now()->addHours(24),
            'paid_at' => null,
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_variant_name' => $variant->name,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 3,
            'unit_price' => 1_000_000,
            'subtotal' => 3_000_000,
        ]);

        $order->load('items.product');

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(null);

        $workflowService
            ->shouldReceive('changeStatus')
            ->once()
            ->andReturnUsing(
                function (
                    Order $order,
                    string $status,
                    string $description,
                    ?string $adminId = null,
                ) {
                    $order->status = $status;
                    $order->save();

                    return $order;
                }
            );

        $orderService = new OrderService(
            paymentService: Mockery::mock(PaymentService::class),
            midtransService: Mockery::mock(MidtransService::class),
            inventoryService: app(ProductInventoryService::class),
            voucherService: app(VoucherService::class),
            numberService: app(OrderNumberService::class),
            workflowService: $workflowService,
            calculatorService: app(OrderCalculatorService::class),
            trackingTokenService: app(OrderTrackingTokenService::class),
            cartService: app(CartService::class),
            adminNotificationService: Mockery::mock(
                AdminNotificationService::class
            ),
        );

        $orderService->cancelOrder(
            $order,
            'Pembatalan test variant'
        );

        $variant->refresh();
        $product->refresh();

        // Locked stock variant dikembalikan ke ready stock.
        $this->assertSame(7, $variant->ready_stock);
        $this->assertSame(0, $variant->locked_stock);

        // Parent product tetap tidak berubah.
        $this->assertSame(0, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);
    }

    public function test_expire_pending_order_releases_variant_locked_stock_back_to_ready_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Variant Expire Test',
            'slug' => 'furniture-variant-expire-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Variant Expire Test',
            'slug' => 'produk-variant-expire-test',
            'description' => 'Produk variant untuk pengujian inventory expire.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 0,
            'locked_stock' => 0,
        ]);

        $variant = ProductVariant::create([
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 4,
            'locked_stock' => 3,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567893',
            'email' => 'variant-expire-inventory@test.test',
            'name' => 'Variant Expire Inventory Customer',
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-VARIANT-EXPIRE-TEST',
            'midtrans_order_id' => 'MIDTRANS-VARIANT-EXPIRE-TEST',
            'tracking_token' => (string) Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 3_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 3_000_000,

            'shipping_address' => [
                'address' => 'Alamat Variant Expire Inventory Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 3,

            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_expired_at' => now()->subMinute(),
            'paid_at' => null,
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_variant_name' => $variant->name,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 3,
            'unit_price' => 1_000_000,
            'subtotal' => 3_000_000,
        ]);

        $order->load('items.product');

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(null);

        $workflowService
            ->shouldReceive('changeStatus')
            ->once()
            ->andReturnUsing(
                function (
                    Order $order,
                    string $status,
                    string $description,
                    ?string $adminId = null,
                ) {
                    $order->status = $status;
                    $order->save();

                    return $order;
                }
            );

        $orderService = new OrderService(
            paymentService: Mockery::mock(PaymentService::class),
            midtransService: Mockery::mock(MidtransService::class),
            inventoryService: app(ProductInventoryService::class),
            voucherService: app(VoucherService::class),
            numberService: app(OrderNumberService::class),
            workflowService: $workflowService,
            calculatorService: app(OrderCalculatorService::class),
            trackingTokenService: app(OrderTrackingTokenService::class),
            cartService: app(CartService::class),
            adminNotificationService: Mockery::mock(
                AdminNotificationService::class
            ),
        );

        $orderService->expireOrder($order);

        $variant->refresh();
        $product->refresh();

        $this->assertSame(7, $variant->ready_stock);
        $this->assertSame(0, $variant->locked_stock);

        $this->assertSame(0, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);
    }

    public function test_cancel_pending_via_cancellation_service_releases_variant_locked_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Variant Cancellation Test',
            'slug' => 'furniture-variant-cancellation-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Variant Cancellation Test',
            'slug' => 'produk-variant-cancellation-test',
            'description' => 'Produk variant untuk pengujian cancellation inventory.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,

            // Parent product tidak menyimpan stok ketika menggunakan variant.
            'ready_stock' => 0,
            'locked_stock' => 0,
        ]);

        $variant = ProductVariant::create([
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 7,
            'locked_stock' => 3,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567894',
            'email' => 'variant-cancellation-inventory@test.test',
            'name' => 'Variant Cancellation Inventory Customer',
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-VARIANT-CANCEL-SERVICE-TEST',
            'midtrans_order_id' => 'MIDTRANS-VARIANT-CANCEL-SERVICE-TEST',
            'tracking_token' => (string) Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 3_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 3_000_000,

            'shipping_address' => [
                'address' => 'Alamat Variant Cancellation Inventory Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 3,

            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_expired_at' => now()->addMinutes(30),
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_variant_name' => $variant->name,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 3,
            'unit_price' => 1_000_000,
            'subtotal' => 3_000_000,
        ]);

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('changeStatus')
            ->once()
            ->andReturnUsing(
                function (
                    Order $order,
                    string $status,
                    string $description,
                    ?string $adminId = null,
                ) {
                    $order->status = $status;
                    $order->save();

                    return $order;
                }
            );

        $cancellationService = new OrderCancellationService(
            workflowService: $workflowService,
            refundService: Mockery::mock(RefundService::class),
            inventoryService: app(ProductInventoryService::class),
            paymentService: Mockery::mock(PaymentService::class),
            adminNotificationService: Mockery::mock(
                AdminNotificationService::class
            ),
        );

        $cancellationService->cancelPending($order);

        $variant->refresh();
        $product->refresh();

        // Locked stock variant dikembalikan ke ready stock.
        $this->assertSame(10, $variant->ready_stock);
        $this->assertSame(0, $variant->locked_stock);

        // Parent product tetap tidak berubah.
        $this->assertSame(0, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_cancel_pending_via_admin_releases_variant_locked_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Admin Variant Cancellation Test',
            'slug' => 'furniture-admin-variant-cancellation-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Admin Variant Cancellation Test',
            'slug' => 'produk-admin-variant-cancellation-test',
            'description' => 'Produk untuk pengujian admin cancellation inventory variant.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 0,
            'locked_stock' => 0,
        ]);

        $variant = ProductVariant::create([
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 7,
            'locked_stock' => 3,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567898',
            'email' => 'admin-variant-cancellation-inventory@test.test',
            'name' => 'Admin Variant Cancellation Customer',
        ]);

        $admin = Admin::create([
            'id' => (string) Str::uuid(),
            'full_name' => 'Admin Variant Inventory Test',
            'email' => 'admin-variant-inventory-test@test.test',
            'password' => 'password',
            'phone_number' => '081234567899',
            'profile_photo' => null,
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-ADMIN-VARIANT-CANCEL-INVENTORY-TEST',
            'midtrans_order_id' => 'MIDTRANS-ADMIN-VARIANT-CANCEL-INVENTORY-TEST',
            'tracking_token' => (string) Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 3_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 3_000_000,

            'shipping_address' => [
                'address' => 'Alamat Admin Variant Cancellation Inventory Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 3,

            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_expired_at' => now()->addMinutes(30),
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_variant_name' => $variant->name,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 3,
            'unit_price' => 1_000_000,
            'subtotal' => 3_000_000,
        ]);

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('changeStatus')
            ->once()
            ->andReturnUsing(
                function (
                    Order $order,
                    string $status,
                    string $description,
                    ?string $adminId = null,
                ) {
                    $order->status = $status;
                    $order->save();

                    return $order;
                }
            );

        $cancellationService = new OrderCancellationService(
            workflowService: $workflowService,
            refundService: Mockery::mock(RefundService::class),
            inventoryService: app(ProductInventoryService::class),
            paymentService: Mockery::mock(PaymentService::class),
            adminNotificationService: Mockery::mock(
                AdminNotificationService::class
            ),
        );

        $cancellationService->cancelByAdmin(
            order: $order,
            admin: $admin,
            reason: 'Testing variant inventory release.',
        );

        $variant->refresh();
        $product->refresh();

        $this->assertSame(10, $variant->ready_stock);
        $this->assertSame(0, $variant->locked_stock);

        $this->assertSame(0, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_cancel_pending_order_releases_locked_stock_back_to_ready_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Cancel Test',
            'slug' => 'furniture-cancel-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Cancel Test',
            'slug' => 'produk-cancel-test',
            'description' => 'Produk untuk pengujian release locked stock.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 7,
            'locked_stock' => 3,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567891',
            'email' => 'cancel-inventory@test.test',
            'name' => 'Cancel Inventory Customer',
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-CANCEL-INVENTORY-TEST',
            'midtrans_order_id' => 'MIDTRANS-CANCEL-INVENTORY-TEST',
            'tracking_token' => (string) Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 3_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 3_000_000,

            'shipping_address' => [
                'address' => 'Alamat Cancel Inventory Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 3,

            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_expired_at' => now()->addHours(24),
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 3,
            'unit_price' => 1_000_000,
            'subtotal' => 3_000_000,
        ]);

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(null);

        $workflowService
            ->shouldReceive('changeStatus')
            ->once()
            ->andReturnUsing(
                function (
                    Order $order,
                    string $status,
                    string $description,
                    ?string $adminId = null,
                ) {
                    $order->status = $status;
                    $order->save();

                    return $order;
                }
            );

        $orderService = new OrderService(
            paymentService: Mockery::mock(PaymentService::class),
            midtransService: Mockery::mock(MidtransService::class),
            inventoryService: app(ProductInventoryService::class),
            voucherService: app(VoucherService::class),
            numberService: app(OrderNumberService::class),
            workflowService: $workflowService,
            calculatorService: app(OrderCalculatorService::class),
            trackingTokenService: app(OrderTrackingTokenService::class),
            cartService: app(CartService::class),
            adminNotificationService: Mockery::mock(
                AdminNotificationService::class
            ),
        );

        $orderService->cancelOrder($order);

        $product->refresh();

        $this->assertSame(10, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_release_locked_stock_returns_reserved_stock_to_ready_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Release Test',
            'slug' => 'furniture-release-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Release Test',
            'slug' => 'produk-release-test',
            'description' => 'Produk untuk pengujian release locked stock.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 7,
            'locked_stock' => 3,
        ]);

        $item = new OrderItem([
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $item->setRelation('product', $product);

        $inventoryService = app(ProductInventoryService::class);

        $inventoryService->releaseLockedStock(
            collect([$item])
        );

        $product->refresh();

        $this->assertSame(10, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);
    }

    public function test_release_locked_stock_returns_variant_reserved_stock_to_ready_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Variant Release Test',
            'slug' => 'furniture-variant-release-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Variant Release Test',
            'slug' => 'produk-variant-release-test',
            'description' => 'Produk untuk pengujian release locked stock variant.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 0,
            'locked_stock' => 0,
        ]);

        $variant = ProductVariant::create([
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 7,
            'locked_stock' => 3,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $item = new OrderItem([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_variant_name' => $variant->name,
            'quantity' => 3,
        ]);

        $item->setRelation('product', $product);
        $item->setRelation('productVariant', $variant);

        $inventoryService = app(ProductInventoryService::class);

        $inventoryService->releaseLockedStock(
            collect([$item])
        );

        $variant->refresh();
        $product->refresh();

        $this->assertSame(10, $variant->ready_stock);
        $this->assertSame(0, $variant->locked_stock);

        $this->assertSame(0, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);
    }

    public function test_expire_pending_order_releases_locked_stock_back_to_ready_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Expire Test',
            'slug' => 'furniture-expire-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Expire Test',
            'slug' => 'produk-expire-test',
            'description' => 'Produk untuk pengujian expired payment.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 7,
            'locked_stock' => 3,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567892',
            'email' => 'expire-inventory@test.test',
            'name' => 'Expire Inventory Customer',
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-EXPIRE-INVENTORY-TEST',
            'midtrans_order_id' => 'MIDTRANS-EXPIRE-INVENTORY-TEST',
            'tracking_token' => (string) Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 3_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 3_000_000,

            'shipping_address' => [
                'address' => 'Alamat Expire Inventory Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 3,

            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_expired_at' => now()->subMinute(),
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 3,
            'unit_price' => 1_000_000,
            'subtotal' => 3_000_000,
        ]);

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(null);

        $workflowService
            ->shouldReceive('changeStatus')
            ->once()
            ->andReturnUsing(
                function (
                    Order $order,
                    string $status,
                    string $description,
                    ?string $adminId = null,
                ) {
                    $order->status = $status;
                    $order->save();

                    return $order;
                }
            );

        $orderService = new OrderService(
            paymentService: Mockery::mock(PaymentService::class),
            midtransService: Mockery::mock(MidtransService::class),
            inventoryService: app(ProductInventoryService::class),
            voucherService: app(VoucherService::class),
            numberService: app(OrderNumberService::class),
            workflowService: $workflowService,
            calculatorService: app(OrderCalculatorService::class),
            trackingTokenService: app(OrderTrackingTokenService::class),
            cartService: app(CartService::class),
            adminNotificationService: Mockery::mock(
                AdminNotificationService::class
            ),
        );

        $orderService->expireOrder($order);

        $product->refresh();

        $this->assertSame(10, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('expired', $order->payment_status);
    }

    public function test_cancel_pending_via_cancellation_service_releases_locked_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Cancellation Test',
            'slug' => 'furniture-cancellation-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Cancellation Test',
            'slug' => 'produk-cancellation-test',
            'description' => 'Produk untuk pengujian cancellation inventory.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 7,
            'locked_stock' => 3,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567893',
            'email' => 'cancellation-inventory@test.test',
            'name' => 'Cancellation Inventory Customer',
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-CANCEL-INVENTORY-TEST',
            'midtrans_order_id' => 'MIDTRANS-CANCEL-INVENTORY-TEST',
            'tracking_token' => (string) Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 3_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 3_000_000,

            'shipping_address' => [
                'address' => 'Alamat Cancellation Inventory Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 3,

            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_expired_at' => now()->addMinutes(30),
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 3,
            'unit_price' => 1_000_000,
            'subtotal' => 3_000_000,
        ]);

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('changeStatus')
            ->once()
            ->andReturnUsing(
                function (
                    Order $order,
                    string $status,
                    string $description,
                    ?string $adminId = null,
                ) {
                    $order->status = $status;
                    $order->save();

                    return $order;
                }
            );

        $cancellationService = new OrderCancellationService(
            workflowService: $workflowService,
            refundService: Mockery::mock(RefundService::class),
            inventoryService: app(ProductInventoryService::class),
            paymentService: Mockery::mock(PaymentService::class),
            adminNotificationService: Mockery::mock(
                AdminNotificationService::class
            ),
        );

        $cancellationService->cancelPending($order);

        $product->refresh();

        $this->assertSame(10, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_cancel_pending_via_admin_releases_locked_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Admin Cancellation Test',
            'slug' => 'furniture-admin-cancellation-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Admin Cancellation Test',
            'slug' => 'produk-admin-cancellation-test',
            'description' => 'Produk untuk pengujian admin cancellation inventory.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 7,
            'locked_stock' => 3,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567894',
            'email' => 'admin-cancellation-inventory@test.test',
            'name' => 'Admin Cancellation Customer',
        ]);

        $admin = Admin::create([
            'id' => (string) Str::uuid(),
            'full_name' => 'Admin Inventory Test',
            'email' => 'admin-inventory-test@test.test',
            'password' => 'password',
            'phone_number' => '081234567895',
            'profile_photo' => null,
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-ADMIN-CANCEL-INVENTORY-TEST',
            'midtrans_order_id' => 'MIDTRANS-ADMIN-CANCEL-INVENTORY-TEST',
            'tracking_token' => (string) Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 3_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 3_000_000,

            'shipping_address' => [
                'address' => 'Alamat Admin Cancellation Inventory Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 3,

            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_expired_at' => now()->addMinutes(30),
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 3,
            'unit_price' => 1_000_000,
            'subtotal' => 3_000_000,
        ]);

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('changeStatus')
            ->once()
            ->andReturnUsing(
                function (
                    Order $order,
                    string $status,
                    string $description,
                    ?string $adminId = null,
                ) {
                    $order->status = $status;
                    $order->save();

                    return $order;
                }
            );

        $cancellationService = new OrderCancellationService(
            workflowService: $workflowService,
            refundService: Mockery::mock(RefundService::class),
            inventoryService: app(ProductInventoryService::class),
            paymentService: Mockery::mock(PaymentService::class),
            adminNotificationService: Mockery::mock(
                AdminNotificationService::class
            ),
        );

        $cancellationService->cancelByAdmin(
            order: $order,
            admin: $admin,
            reason: 'Testing inventory release.',
        );

        $product->refresh();

        $this->assertSame(10, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_cancel_paid_order_via_admin_restores_sold_stock_to_ready_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Paid Cancellation Test',
            'slug' => 'furniture-paid-cancellation-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Paid Cancellation Test',
            'slug' => 'produk-paid-cancellation-test',
            'description' => 'Produk untuk pengujian paid cancellation inventory.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 7,
            'locked_stock' => 0,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567896',
            'email' => 'paid-cancellation-inventory@test.test',
            'name' => 'Paid Cancellation Customer',
        ]);

        $admin = Admin::create([
            'id' => (string) Str::uuid(),
            'full_name' => 'Admin Paid Cancellation Test',
            'email' => 'admin-paid-cancellation@test.test',
            'password' => 'password',
            'phone_number' => '081234567897',
            'profile_photo' => null,
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-PAID-CANCEL-INVENTORY-TEST',
            'midtrans_order_id' => 'MIDTRANS-PAID-CANCEL-INVENTORY-TEST',
            'tracking_token' => (string) Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 3_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 3_000_000,

            'shipping_address' => [
                'address' => 'Alamat Paid Cancellation Inventory Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 3,

            'status' => 'paid',
            'payment_status' => 'paid',
            'payment_expired_at' => now()->subMinutes(30),
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 3,
            'unit_price' => 1_000_000,
            'subtotal' => 3_000_000,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'TEST-PAID-ADMIN-CANCEL',
            'transaction_status' => 'settlement',
            'gross_amount' => $order->total_payment,
            'expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('changeStatus')
            ->once()
            ->andReturnUsing(
                function (
                    Order $order,
                    string $status,
                    string $description,
                    ?string $adminId = null,
                ) {
                    $order->status = $status;
                    $order->save();

                    return $order;
                }
            );

        $paymentService = Mockery::mock(
            PaymentService::class
        );

        $paymentService
            ->shouldReceive('isPaid')
            ->twice()
            ->with(Mockery::type(\App\Models\Payment::class))
            ->andReturn(true);

        $refundService = Mockery::mock(
            RefundService::class
        );

        $refundService
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::type(Order::class))
            ->andReturn(
                new \App\Models\Refund()
            );

        $cancellationService = new OrderCancellationService(
            workflowService: $workflowService,
            refundService: $refundService,
            inventoryService: app(ProductInventoryService::class),
            paymentService: $paymentService,
            adminNotificationService: Mockery::mock(
                AdminNotificationService::class
            ),
        );

        $cancellationService->cancelByAdmin(
            order: $order,
            admin: $admin,
            reason: 'Testing paid inventory restoration.',
        );

        $product->refresh();

        $this->assertSame(10, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('paid', $order->payment_status);
    }

    public function test_cancel_paid_order_via_admin_restores_variant_sold_stock_to_ready_stock(): void
    {
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'name' => 'Furniture Paid Variant Cancellation Test',
            'slug' => 'furniture-paid-variant-cancellation-test',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Produk Paid Variant Cancellation Test',
            'slug' => 'produk-paid-variant-cancellation-test',
            'description' => 'Produk untuk pengujian paid cancellation inventory variant.',
            'original_price' => 1_000_000,
            'discount_price' => null,
            'is_sale' => false,
            'ready_stock' => 0,
            'locked_stock' => 0,
        ]);

        $variant = ProductVariant::create([
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'name' => 'Natural',
            'ready_stock' => 7,
            'locked_stock' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'phone' => '081234567900',
            'email' => 'paid-variant-cancellation-inventory@test.test',
            'name' => 'Paid Variant Cancellation Customer',
        ]);

        $admin = Admin::create([
            'id' => (string) Str::uuid(),
            'full_name' => 'Admin Paid Variant Cancellation Test',
            'email' => 'admin-paid-variant-cancellation@test.test',
            'password' => 'password',
            'phone_number' => '081234567901',
            'profile_photo' => null,
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_number' => 'ORD-PAID-VARIANT-CANCEL-INVENTORY-TEST',
            'midtrans_order_id' => 'MIDTRANS-PAID-VARIANT-CANCEL-INVENTORY-TEST',
            'tracking_token' => (string) Str::ulid(),

            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,

            'total_product_price' => 3_000_000,
            'voucher_discount_amount' => 0,
            'original_shipping_fee' => 0,
            'shipping_fee' => 0,
            'total_payment' => 3_000_000,

            'shipping_address' => [
                'address' => 'Alamat Paid Variant Cancellation Inventory Test',
                'city' => 'Malang',
            ],

            'shipping_method' => 'manual',
            'courier' => null,
            'tracking_number' => null,
            'total_weight' => 3,

            'status' => 'paid',
            'payment_status' => 'paid',
            'payment_expired_at' => now()->subMinutes(30),
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_variant_name' => $variant->name,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_image' => null,
            'quantity' => 3,
            'unit_price' => 1_000_000,
            'subtotal' => 3_000_000,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'TEST-PAID-ADMIN-VARIANT-CANCEL',
            'transaction_status' => 'settlement',
            'gross_amount' => $order->total_payment,
            'expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('changeStatus')
            ->once()
            ->andReturnUsing(
                function (
                    Order $order,
                    string $status,
                    string $description,
                    ?string $adminId = null,
                ) {
                    $order->status = $status;
                    $order->save();

                    return $order;
                }
            );

        $paymentService = Mockery::mock(
            PaymentService::class
        );

        $paymentService
            ->shouldReceive('isPaid')
            ->twice()
            ->with(Mockery::type(\App\Models\Payment::class))
            ->andReturn(true);

        $refundService = Mockery::mock(
            RefundService::class
        );

        $refundService
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::type(Order::class))
            ->andReturn(
                new \App\Models\Refund()
            );

        $cancellationService = new OrderCancellationService(
            workflowService: $workflowService,
            refundService: $refundService,
            inventoryService: app(ProductInventoryService::class),
            paymentService: $paymentService,
            adminNotificationService: Mockery::mock(
                AdminNotificationService::class
            ),
        );

        $cancellationService->cancelByAdmin(
            order: $order,
            admin: $admin,
            reason: 'Testing paid variant inventory restoration.',
        );

        $variant->refresh();
        $product->refresh();

        $this->assertSame(10, $variant->ready_stock);
        $this->assertSame(0, $variant->locked_stock);

        $this->assertSame(0, $product->ready_stock);
        $this->assertSame(0, $product->locked_stock);

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('paid', $order->payment_status);
    }
}