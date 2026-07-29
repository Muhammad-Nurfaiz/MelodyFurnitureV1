<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Voucher;
use App\Models\Customer;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\Payment\PaymentService;
use App\Services\Payment\MidtransService;
use App\Services\Inventory\ProductInventoryService;
use App\Services\Voucher\VoucherService;
use App\Models\OrderStatusHistory;
use App\Services\Cart\CartService;
use App\Services\Shipping\ShippingService;
use App\Services\Order\OrderNumberService;
use App\Services\Order\OrderWorkflowService;
use App\Services\Order\OrderCalculatorService;
use App\Services\Order\OrderTrackingTokenService;
use Illuminate\Support\Facades\Log;

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

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {

            /**
             * Step 1
             * Create Order
             */

            /**
             * Step 2
             * Create Order Items
             */

            /**
             * Step 3
             * Create Payment
             */

            /**
             * Step 4
             * Create History
             */

            /**
             * Step 5
             * Return Order
             */

        });
    }

    public function checkout(Customer $customer,Collection $products,?Voucher $voucher,array $shipping): Order {
        try {
            return DB::transaction(function () use ($customer,$products,$voucher,$shipping) {
                    /*
                    |--------------------------------------------------------------------------
                    | Validate Product Stock
                    |--------------------------------------------------------------------------
                    */
                    $this->inventoryService->validateStock($products);

                    /*
                    |--------------------------------------------------------------------------
                    | Calculate
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
                    | Create Order
                    |--------------------------------------------------------------------------
                    */
                    $orderNumber = $this->numberService->generate();

                    $order = $this->createOrder([
                        'customer_id' => $customer->id,
                        'voucher_id' => $voucher?->id,
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
                    | Order Items
                    |--------------------------------------------------------------------------
                    */

                    $this->createOrderItems($order,$products);
                    $order->load(['customer','items','payment']);

                    /*
                    |--------------------------------------------------------------------------
                    | Reduce Stock
                    |--------------------------------------------------------------------------
                    */
                    $this->inventoryService->decreaseStock($products);

                    /*
                    |--------------------------------------------------------------------------
                    | Midtrans
                    |--------------------------------------------------------------------------
                    */
                    try {
                        $snap = $this->midtransService->createTransaction($order);
                    } catch (\Throwable $e) {
                        throw new RuntimeException(
                            'Gagal membuat transaksi pembayaran.',
                            previous: $e
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Payment
                    |--------------------------------------------------------------------------
                    */
                    $this->paymentService->create($order,$snap);

                    /*
                    |--------------------------------------------------------------------------
                    | History
                    |--------------------------------------------------------------------------
                    */
                    $this->workflowService->initialize($order,'Checkout dibuat');
                    $customer->loadMissing('cart');
                    if ($customer->cart) {
                        $this->cartService->clearCart($customer->cart);
                    }
                    return $order->fresh(['customer','items','payment',]);
                });

        } catch (\Throwable $e) {

            dd([
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

        }
    }

    private function createOrder(array $data): Order {
        return Order::create($data);
    }

    private function createOrderItems(Order $order, Collection $products): void {
        foreach ($products as $item) {
            $product = $item->product;
            $price = $product->is_sale
                && $product->discount_price
                    ? $product->discount_price
                    : $product->original_price;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'product_image' => $product->thumbnail?->url
                    ?? $product->thumbnail?->media_url,
                'quantity' => $item->quantity,
                'unit_price' => $price,
                'subtotal' => $price * $item->quantity,
            ]);
        }
    }

    public function cancelOrder(Order $order,?string $reason = null,?string $createdBy = null): Order{
        $this->workflowService->validate($order,'cancelled');

        return DB::transaction(function () use ($order,$reason,$createdBy) {
            $order->loadMissing(['items.product',]);
            /*
            |--------------------------------------------------------------------------
            | Restore Product Stock
            |--------------------------------------------------------------------------
            */
            $this->inventoryService->increaseStock($this->inventoryItems($order));

            /*
            |--------------------------------------------------------------------------
            | Update Order
            |--------------------------------------------------------------------------
            */
            $this->workflowService->changeStatus($order,'cancelled',$reason,$createdBy);
            return $order->fresh(['payment','items.product',]);
        });
    }

    public function expireOrder(Order $order,?string $description = 'Payment expired',?string $createdBy = null): Order {
        $this->workflowService->validate($order,'cancelled');
        return DB::transaction(function () use ($order,$description,$createdBy) {
            $order->update(['payment_status'=>'expired',]);
            /*
            |--------------------------------------------------------------------------
            | Load Relation
            |--------------------------------------------------------------------------
            */
            $order->loadMissing(['items.product',]);

            /*
            |--------------------------------------------------------------------------
            | Restore Product Stock
            |--------------------------------------------------------------------------
            */
            $this->inventoryService->increaseStock($this->inventoryItems($order));

            /*
            |--------------------------------------------------------------------------
            | Update Order
            |--------------------------------------------------------------------------
            */
            $this->workflowService->changeStatus($order,'cancelled',$description,$createdBy);
            return $order->fresh(['items.product','payment']);
        });
    }

    public function markPaid(Order $order,?string $createdBy = null): Order {

        /*
        |--------------------------------------------------------------------------
        | Idempotent
        |--------------------------------------------------------------------------
        */

        if ($order->status === 'paid' && $order->payment_status === 'paid') {
            return $order->fresh(['payment','items.product',]);
        }
        $this->workflowService->validate($order,'paid');
        return DB::transaction(function () use ($order,$createdBy) {
            $order->update(['payment_status' => 'paid',]);
            $this->workflowService->changeStatus($order,'paid','Payment berhasil diterima',$createdBy);
            return $order->fresh(['payment','items.product',]);
        });
    }

    private function inventoryItems(Order $order): Collection
    {
        return $order->items;
    }
}