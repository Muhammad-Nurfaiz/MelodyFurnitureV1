<?php

namespace Tests\Unit\Services\Order;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\Voucher;
use App\Models\Payment;
use App\Services\Cart\CartService;
use App\Services\Inventory\ProductInventoryService;
use App\Services\Order\OrderCalculatorService;
use App\Services\Order\OrderNumberService;
use App\Services\Order\OrderService;
use App\Services\Order\OrderTrackingTokenService;
use App\Services\Order\OrderWorkflowService;
use App\Services\Payment\MidtransService;
use App\Services\Payment\PaymentService;
use App\Services\Shipping\ShippingService;
use App\Services\Voucher\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class OrderVoucherTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $cartCustomer;
    protected Customer $customer;
    protected Category $category;
    protected Product $product;
    protected Cart $cart;
    protected Voucher $voucher;

    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        |
        | products.category_id adalah NOT NULL dan merupakan foreign key.
        |
        */

        $this->category = Category::create([
            'name' => 'Test Furniture',
            'slug' => 'test-furniture-category',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cart Customer
        |--------------------------------------------------------------------------
        |
        | Customer ini adalah pemilik cart / guest session.
        |
        */

        $this->cartCustomer = Customer::create([
            'name' => 'Guest Cart Customer',
            'email' => 'guest-cart@test.local',
            'phone' => '081234567891',
            'guest_token' => 'guest-token-test',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Checkout Customer
        |--------------------------------------------------------------------------
        |
        | Customer ini adalah customer yang digunakan pada Order.
        |
        */

        $this->customer = Customer::create([
            'name' => 'Checkout Customer',
            'email' => 'checkout@test.local',
            'phone' => '081234567892',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Product
        |--------------------------------------------------------------------------
        |
        | category_id wajib diisi karena migration products menggunakan:
        |
        | foreignUuid('category_id')->constrained()
        |
        | description juga NOT NULL.
        |
        */

        $this->product = Product::create([
            'category_id' => $this->category->id,

            'name' => 'Test Furniture',
            'slug' => 'test-furniture',

            'description' => 'Produk furniture untuk kebutuhan testing.',

            'original_price' => 1_000_000,
            'discount_price' => null,
            'discount_percentage' => null,

            'is_sale' => false,

            'ready_stock' => 10,
            'locked_stock' => 0,

            'origin_city' => 'Malang',

            'average_rating' => 0,
            'total_sold' => 0,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Product Specification
        |--------------------------------------------------------------------------
        |
        | OrderCalculatorService / proses checkout membutuhkan
        | product.specification.
        |
        */

        ProductSpecification::create([
            'product_id' => $this->product->id,
            'dimensions' => '100 x 50 x 40 cm',
            'weight' => 10,
            'packing_weight' => 12,
            'load_capacity' => '100 kg',
            'material_details' => 'Kayu solid',
            'assembly_required' => false,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cart
        |--------------------------------------------------------------------------
        */

        $this->cart = Cart::create([
            'customer_id' => $this->cartCustomer->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cart Item
        |--------------------------------------------------------------------------
        */

        CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Voucher
        |--------------------------------------------------------------------------
        */

        $this->voucher = Voucher::create([
            'code' => 'ORDERTEST10',

            'discount_type' => 'percentage',
            'discount_value' => 10,

            'min_order_amount' => 0,
            'max_discount_amount' => null,

            'start_date' => null,
            'expiry_date' => now()->addDays(7),

            'usage_limit' => 10,
            'used_count' => 0,

            'is_active' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Order Service
    |--------------------------------------------------------------------------
    */

    protected function makeOrderService(): OrderService
    {
        /*
        |--------------------------------------------------------------------------
        | Voucher Service
        |--------------------------------------------------------------------------
        */

        $voucherService = app(VoucherService::class);

        /*
        |--------------------------------------------------------------------------
        | Shipping Service
        |--------------------------------------------------------------------------
        */

        $shippingService = app(ShippingService::class);

        /*
        |--------------------------------------------------------------------------
        | Order Calculator
        |--------------------------------------------------------------------------
        |
        | Calculator di-mock karena OrderVoucherTest fokus pada:
        |
        | Order
        | Voucher
        | Cart
        |
        | Bukan pengujian OrderCalculatorService.
        |
        */

        $calculatorService = Mockery::mock(
            OrderCalculatorService::class
        );

        $calculatorService
            ->shouldReceive('calculate')
            ->andReturnUsing(
                function (
                    Collection $products,
                    ?Voucher $voucher,
                    string $courier,
                    string $service
                ): array {
                    $voucherDiscount = $voucher
                        ? 100_000
                        : 0;

                    return [
                        'subtotal' => 1_000_000,
                        'total_weight' => 12,
                        'voucher_discount' => $voucherDiscount,
                        'shipping_fee' => 25_000,
                        'total_payment' =>
                            1_000_000
                            - $voucherDiscount
                            + 25_000,
                    ];
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Payment Service
        |--------------------------------------------------------------------------
        */

        $paymentService = Mockery::mock(PaymentService::class);

        $paymentService
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function () {
                return new Payment();
            });

        /*
        |--------------------------------------------------------------------------
        | Midtrans Service
        |--------------------------------------------------------------------------
        */

        $midtransService = Mockery::mock(
            MidtransService::class
        );

        $midtransService
            ->shouldReceive('createTransaction')
            ->andReturn([
                'transaction_id' => null,
                'order_id' => 'TEST-MIDTRANS-ORDER',
                'snap_token' => 'TEST-SNAP-TOKEN',
                'redirect_url' => 'https://example.test/payment',
                'expiry_time' => now()->addMinutes(30),
                'payload' => [],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Inventory Service
        |--------------------------------------------------------------------------
        */

        $inventoryService = Mockery::mock(
            ProductInventoryService::class
        );

        $inventoryService
            ->shouldReceive('validateStock')
            ->andReturn(null);

        $inventoryService
            ->shouldReceive('decreaseStock')
            ->andReturn(null);

        /*
        |--------------------------------------------------------------------------
        | Order Number Service
        |--------------------------------------------------------------------------
        */

        $numberService = Mockery::mock(
            OrderNumberService::class
        );

        $numberService
            ->shouldReceive('generate')
            ->andReturn('ORDER-TEST-0001');

        /*
        |--------------------------------------------------------------------------
        | Workflow Service
        |--------------------------------------------------------------------------
        */

        $workflowService = Mockery::mock(
            OrderWorkflowService::class
        );

        $workflowService
            ->shouldReceive('initialize')
            ->andReturnUsing(
                function (Order $order, string $note): Order {
                    return $order;
                }
            );

        $workflowService
            ->shouldReceive('validate')
            ->andReturn(null);

        $workflowService
            ->shouldReceive('changeStatus')
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

        /*
        |--------------------------------------------------------------------------
        | Tracking Token Service
        |--------------------------------------------------------------------------
        */

        $trackingTokenService = Mockery::mock(
            OrderTrackingTokenService::class
        );

        $trackingTokenService
            ->shouldReceive('generate')
            ->andReturn('TEST-TRACKING-TOKEN');

        /*
        |--------------------------------------------------------------------------
        | Cart Service
        |--------------------------------------------------------------------------
        |
        | Checkout berhasil harus menghapus seluruh item cart.
        |
        */

        $cartService = Mockery::mock(
            CartService::class
        );

        $cartService
            ->shouldReceive('clearCart')
            ->once()
            ->andReturnUsing(
                function (Cart $cart): void {
                    $cart->items()->delete();
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Return Order Service
        |--------------------------------------------------------------------------
        */

        return new OrderService(
            paymentService: $paymentService,
            midtransService: $midtransService,
            inventoryService: $inventoryService,
            voucherService: $voucherService,
            shippingService: $shippingService,
            numberService: $numberService,
            workflowService: $workflowService,
            calculatorService: $calculatorService,
            trackingTokenService: $trackingTokenService,
            cartService: $cartService,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Checkout Products
    |--------------------------------------------------------------------------
    */

    protected function checkoutProducts(): Collection
    {
        return $this->cart
            ->fresh([
                'items.product.thumbnail',
                'items.product.specification',
            ])
            ->items;
    }

    /*
    |--------------------------------------------------------------------------
    | Shipping
    |--------------------------------------------------------------------------
    */

    protected function shipping(): array
    {
        return [
            'courier' => 'jne',

            'service' => 'REG',

            'address' => [
                'recipient_name' => 'Test Customer',
                'phone' => '081234567892',
                'address' => 'Jl. Test No. 1',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65100',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Test: Checkout saves voucher and discount to order
    |--------------------------------------------------------------------------
    */

    public function test_checkout_saves_voucher_and_discount_to_order(): void
    {
        $service = $this->makeOrderService();

        $order = $service->checkout(
            customer: $this->customer,
            cartCustomer: $this->cartCustomer,
            products: $this->checkoutProducts(),
            voucher: $this->voucher,
            shipping: $this->shipping(),
        );

        /*
        |--------------------------------------------------------------------------
        | Voucher Relationship ID
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            $this->voucher->id,
            $order->voucher_id
        );

        /*
        |--------------------------------------------------------------------------
        | Voucher Discount
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            100_000,
            (float) $order->voucher_discount_amount
        );

        /*
        |--------------------------------------------------------------------------
        | Product Subtotal
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            1_000_000,
            (float) $order->total_product_price
        );

        /*
        |--------------------------------------------------------------------------
        | Total Payment
        |--------------------------------------------------------------------------
        |
        | 1.000.000
        | - 100.000 voucher
        | + 25.000 shipping
        | = 925.000
        |
        */

        $this->assertEquals(
            925_000,
            (float) $order->total_payment
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Test: Checkout does not mark voucher as used
    |--------------------------------------------------------------------------
    */

    public function test_checkout_does_not_mark_voucher_as_used(): void
    {
        $service = $this->makeOrderService();

        $service->checkout(
            customer: $this->customer,
            cartCustomer: $this->cartCustomer,
            products: $this->checkoutProducts(),
            voucher: $this->voucher,
            shipping: $this->shipping(),
        );

        /*
        |--------------------------------------------------------------------------
        | Voucher hanya disimpan pada Order.
        |
        | Voucher belum dianggap digunakan sampai payment berhasil.
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas('vouchers', [
            'id' => $this->voucher->id,
            'used_count' => 0,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Test: Checkout clears cart after successful order
    |--------------------------------------------------------------------------
    */

    public function test_checkout_clears_cart_after_successful_order(): void
    {
        $service = $this->makeOrderService();

        $service->checkout(
            customer: $this->customer,
            cartCustomer: $this->cartCustomer,
            products: $this->checkoutProducts(),
            voucher: $this->voucher,
            shipping: $this->shipping(),
        );

        /*
        |--------------------------------------------------------------------------
        | Cart harus kosong setelah checkout berhasil.
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $this->cart->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Test: Order is connected to voucher
    |--------------------------------------------------------------------------
    */

    public function test_order_is_connected_to_voucher(): void
    {
        $service = $this->makeOrderService();

        $order = $service->checkout(
            customer: $this->customer,
            cartCustomer: $this->cartCustomer,
            products: $this->checkoutProducts(),
            voucher: $this->voucher,
            shipping: $this->shipping(),
        );

        /*
        |--------------------------------------------------------------------------
        | Voucher Relationship
        |--------------------------------------------------------------------------
        */

        $order->load('voucher');

        $this->assertInstanceOf(
            Voucher::class,
            $order->voucher
        );

        $this->assertSame(
            $this->voucher->id,
            $order->voucher->id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Test: Paid order marks voucher as used
    |--------------------------------------------------------------------------
    */

    public function test_paid_order_marks_voucher_as_used(): void
    {
        $service = $this->makeOrderService();

        /*
        |--------------------------------------------------------------------------
        | Checkout
        |--------------------------------------------------------------------------
        */

        $order = $service->checkout(
            customer: $this->customer,
            cartCustomer: $this->cartCustomer,
            products: $this->checkoutProducts(),
            voucher: $this->voucher,
            shipping: $this->shipping(),
        );

        /*
        |--------------------------------------------------------------------------
        | Load Voucher Relationship
        |--------------------------------------------------------------------------
        |
        | markPaid() menggunakan:
        |
        | if ($order->voucher) {
        |     $this->voucherService->markUsed(
        |         $order->voucher
        |     );
        | }
        |
        */

        $order->load('voucher');

        /*
        |--------------------------------------------------------------------------
        | Mark Paid
        |--------------------------------------------------------------------------
        */

        $service->markPaid($order);

        /*
        |--------------------------------------------------------------------------
        | Voucher Usage
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas('vouchers', [
            'id' => $this->voucher->id,
            'used_count' => 1,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Order Payment Status
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'paid',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Test: Order without voucher does not change voucher usage
    |--------------------------------------------------------------------------
    */

    public function test_order_without_voucher_does_not_change_voucher_usage(): void
    {
        $service = $this->makeOrderService();

        /*
        |--------------------------------------------------------------------------
        | Checkout Without Voucher
        |--------------------------------------------------------------------------
        */

        $order = $service->checkout(
            customer: $this->customer,
            cartCustomer: $this->cartCustomer,
            products: $this->checkoutProducts(),
            voucher: null,
            shipping: $this->shipping(),
        );

        /*
        |--------------------------------------------------------------------------
        | Order Voucher
        |--------------------------------------------------------------------------
        */

        $this->assertNull(
            $order->voucher_id
        );

        /*
        |--------------------------------------------------------------------------
        | Voucher Discount
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            0,
            (float) $order->voucher_discount_amount
        );

        /*
        |--------------------------------------------------------------------------
        | Mark Paid
        |--------------------------------------------------------------------------
        */

        $service->markPaid($order);

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'voucher_id' => null,
            'voucher_discount_amount' => 0,
            'payment_status' => 'paid',
            'status' => 'paid',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Voucher
        |--------------------------------------------------------------------------
        |
        | Voucher yang tidak digunakan oleh order tidak boleh berubah.
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas('vouchers', [
            'id' => $this->voucher->id,
            'used_count' => 0,
        ]);
    }
}