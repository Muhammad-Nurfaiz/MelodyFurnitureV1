<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Voucher;
use App\Models\Customer;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

use App\Services\Payment\PaymentService;
use App\Services\Payment\MidtransService;
use App\Services\Inventory\ProductInventoryService;
use App\Services\Voucher\VoucherService;
use App\Services\Cart\CartService;
use App\Services\Shipping\ShippingService;

class OrderService
{
    public function __construct(
        protected PaymentService $paymentService,
        protected MidtransService $midtransService,
        protected ProductInventoryService $inventoryService,
        protected VoucherService $voucherService,
        protected ShippingService $shippingService,
        protected OrderNumberService $numberService,
        protected OrderWorkflowService $workflowService,
        protected OrderCalculatorService $calculatorService,
        protected OrderTrackingTokenService $trackingTokenService,
        protected CartService $cartService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Create Order
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = $this->createOrder($data);

            $this->workflowService->initialize(
                $order,
                'Pesanan dibuat'
            );

            return $order->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Checkout
    |--------------------------------------------------------------------------
    */

    public function checkout(
        Customer $customer,
        Customer $cartCustomer,
        Collection $products,
        ?Voucher $voucher,
        array $shipping
    ): Order {
        return DB::transaction(function () use (
            $customer,
            $cartCustomer,
            $products,
            $voucher,
            $shipping
        ) {

            /*
            |--------------------------------------------------------------------------
            | Validate Shipping
            |--------------------------------------------------------------------------
            */

            if (
                empty($shipping['courier']) ||
                empty($shipping['service']) ||
                empty($shipping['address'])
            ) {
                throw new RuntimeException(
                    'Data pengiriman tidak lengkap.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Product Stock
            |--------------------------------------------------------------------------
            */

            $this->inventoryService->validateStock($products);

            /*
            |--------------------------------------------------------------------------
            | Calculate Order
            |--------------------------------------------------------------------------
            */

            $summary = $this->calculatorService->calculate(
                products: $products,
                voucher: $voucher,
                courier: $shipping['courier'],
                service: $shipping['service'],
            );

            /*
            |--------------------------------------------------------------------------
            | Generate Order Identity
            |--------------------------------------------------------------------------
            */

            $orderNumber = $this->numberService->generate();

            /*
            |--------------------------------------------------------------------------
            | Create Order
            |--------------------------------------------------------------------------
            */

            $order = $this->createOrder([
                'customer_id' => $customer->id,
                'voucher_id' => $voucher?->id,

                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'customer_address' => $shipping['address'],

                'order_number' => $orderNumber,
                'midtrans_order_id' => $orderNumber,
                'tracking_token' => $this->trackingTokenService->generate(),

                'total_product_price' => $summary['subtotal'],
                'voucher_discount_amount' => $summary['voucher_discount'],

                'original_shipping_fee' => $summary['shipping_fee'],
                'shipping_fee' => $summary['shipping_fee'],

                'total_payment' => $summary['total_payment'],
                'total_weight' => $summary['total_weight'],

                'shipping_method' => $shipping['service'],
                'courier' => $shipping['courier'],
                'shipping_address' => $shipping['address'],

                'status' => 'pending',
                'payment_status' => 'pending',

                'payment_expired_at' => now()->addMinutes(
                    (int) config('payment.expired_minutes')
                ),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Order Items
            |--------------------------------------------------------------------------
            */

            $this->createOrderItems(
                $order,
                $products
            );

            /*
            |--------------------------------------------------------------------------
            | Decrease Stock
            |--------------------------------------------------------------------------
            */

            $this->inventoryService->decreaseStock(
                $products
            );

            /*
            |--------------------------------------------------------------------------
            | Create Midtrans Transaction
            |--------------------------------------------------------------------------
            */

            try {
                $snap = $this->midtransService->createTransaction(
                    $order->fresh(['customer', 'items'])
                );
            } catch (\Throwable $e) {

                Log::error(
                    'Failed to create Midtrans transaction',
                    [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'message' => $e->getMessage(),
                    ]
                );

                throw new RuntimeException(
                    'Gagal membuat transaksi pembayaran.',
                    previous: $e
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create Payment
            |--------------------------------------------------------------------------
            */

            $this->paymentService->create(
                $order,
                $snap
            );

            /*
            |--------------------------------------------------------------------------
            | Initialize Timeline
            |--------------------------------------------------------------------------
            */

            $this->workflowService->initialize(
                $order,
                'Checkout dibuat'
            );

            /*
            |--------------------------------------------------------------------------
            | Clear Cart
            |--------------------------------------------------------------------------
            */

            $cartCustomer->loadMissing('cart');

            if ($cartCustomer->cart) {
                $this->cartService->clearCart(
                    $cartCustomer->cart
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Return
            |--------------------------------------------------------------------------
            */

            return $order->fresh([
                'customer',
                'items',
                'payment',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Create Order
    |--------------------------------------------------------------------------
    */

    private function createOrder(array $data): Order
    {
        return Order::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Order Items
    |--------------------------------------------------------------------------
    */

    private function createOrderItems(
        Order $order,
        Collection $products
    ): void {
        foreach ($products as $item) {

            $product = $item->product;

            $price =
                $product->is_sale &&
                $product->discount_price
                    ? $product->discount_price
                    : $product->original_price;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,

                'product_name' => $product->name,
                'product_slug' => $product->slug,

                'product_image' =>
                    $product->thumbnail?->url
                    ?? $product->thumbnail?->media_url,

                'quantity' => $item->quantity,

                'unit_price' => $price,

                'subtotal' =>
                    $price * $item->quantity,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Order
    |--------------------------------------------------------------------------
    */

    public function cancelOrder(
        Order $order,
        ?string $reason = null,
        ?string $adminId = null
    ): Order {

        /*
        |--------------------------------------------------------------------------
        | Idempotent
        |--------------------------------------------------------------------------
        */

        if ($order->status === 'cancelled') {
            return $order->fresh([
                'payment',
                'items.product',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Transition
        |--------------------------------------------------------------------------
        */

        $this->workflowService->validate(
            $order,
            'cancelled'
        );

        return DB::transaction(function () use (
            $order,
            $reason,
            $adminId
        ) {

            /*
            |--------------------------------------------------------------------------
            | Lock Order
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Re-check Status After Lock
            |--------------------------------------------------------------------------
            */

            if ($order->status === 'cancelled') {
                return $order->fresh([
                    'payment',
                    'items.product',
                ]);
            }

            $this->workflowService->validate(
                $order,
                'cancelled'
            );

            /*
            |--------------------------------------------------------------------------
            | Load Items
            |--------------------------------------------------------------------------
            */

            $order->loadMissing([
                'items.product',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Restore Stock
            |--------------------------------------------------------------------------
            */

            $this->inventoryService->increaseStock(
                $this->inventoryItems($order)
            );

            /*
            |--------------------------------------------------------------------------
            | Change Order Status
            |--------------------------------------------------------------------------
            */

            $this->workflowService->changeStatus(
                order: $order,
                status: 'cancelled',
                description:
                    $reason
                    ?? 'Pesanan dibatalkan.',
                adminId: $adminId,
            );

            return $order->fresh([
                'payment',
                'items.product',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Expire Order
    |--------------------------------------------------------------------------
    */

    public function expireOrder(
        Order $order,
        ?string $description = 'Payment expired',
        ?string $adminId = null
    ): Order {

        /*
        |--------------------------------------------------------------------------
        | Idempotent
        |--------------------------------------------------------------------------
        */

        if ($order->status === 'cancelled') {
            return $order->fresh([
                'payment',
                'items.product',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Transition
        |--------------------------------------------------------------------------
        */

        $this->workflowService->validate(
            $order,
            'cancelled'
        );

        return DB::transaction(function () use (
            $order,
            $description,
            $adminId
        ) {

            /*
            |--------------------------------------------------------------------------
            | Lock Order
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Re-check After Lock
            |--------------------------------------------------------------------------
            */

            if ($order->status === 'cancelled') {
                return $order->fresh([
                    'payment',
                    'items.product',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Transition
            |--------------------------------------------------------------------------
            */

            $this->workflowService->validate(
                $order,
                'cancelled'
            );

            /*
            |--------------------------------------------------------------------------
            | Load Items
            |--------------------------------------------------------------------------
            */

            $order->loadMissing([
                'items.product',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Restore Stock
            |--------------------------------------------------------------------------
            */

            $this->inventoryService->increaseStock(
                $this->inventoryItems($order)
            );

            /*
            |--------------------------------------------------------------------------
            | Payment Status
            |--------------------------------------------------------------------------
            */

            $order->update([
                'payment_status' => 'expired',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Order Status
            |--------------------------------------------------------------------------
            */

            $this->workflowService->changeStatus(
                order: $order,
                status: 'cancelled',
                description: $description,
                adminId: $adminId,
            );

            return $order->fresh([
                'payment',
                'items.product',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Paid
    |--------------------------------------------------------------------------
    */

    public function markPaid(
        Order $order,
        ?string $adminId = null
    ): Order {

        return DB::transaction(function () use (
            $order,
            $adminId
        ) {

            /*
            |--------------------------------------------------------------------------
            | Lock Order
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Idempotent
            |--------------------------------------------------------------------------
            */

            if (
                $order->status === 'paid'
                &&
                $order->payment_status === 'paid'
            ) {
                return $order->fresh([
                    'payment',
                    'items.product',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Workflow
            |--------------------------------------------------------------------------
            */

            $this->workflowService->validate(
                $order,
                'paid'
            );

            /*
            |--------------------------------------------------------------------------
            | Payment Status
            |--------------------------------------------------------------------------
            */

            $order->update([
                'payment_status' => 'paid',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Order Status
            |--------------------------------------------------------------------------
            */

            if ($order->voucher) {
                $this->voucherService->markUsed(
                    $order->voucher
                );
            }

            $this->workflowService->changeStatus(
                order: $order,
                status: 'paid',
                description: 'Payment berhasil diterima',
                adminId: $adminId,
            );

            return $order->fresh([
                'payment',
                'items.product',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory Items
    |--------------------------------------------------------------------------
    */

    private function inventoryItems(
        Order $order
    ): Collection {
        return $order->items;
    }
}