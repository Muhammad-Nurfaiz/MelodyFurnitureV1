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
use App\Services\Shipping\ShippingService;
use App\Services\Order\OrderNumberService;
use App\Services\Order\OrderWorkflowService;
use App\Services\Order\OrderCalculatorService;

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

    public function checkout(
        Customer $customer,
        Collection $products,
        ?Voucher $voucher,
        array $shipping
    ): Order {

        return DB::transaction(function () use (

            $customer,
            $products,
            $voucher,
            $shipping

        ) {

            /*
            |--------------------------------------------------------------------------
            | Validate Product Stock
            |--------------------------------------------------------------------------
            */
            $this->inventoryService
                ->validateStock($products);

            /*
            |--------------------------------------------------------------------------
            | Calculate
            |--------------------------------------------------------------------------
            */
            $summary =
                $this->calculatorService
                    ->calculate(
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
            $order =
                $this->createOrder([
                    'customer_id' => $customer->id,
                    'voucher_id' => $voucher?->id,
                    'order_number' => $this->numberService->generate(),
                    'subtotal' => $summary['subtotal'],
                    'voucher_discount' => $summary['voucher_discount'],
                    'shipping_fee' => $summary['shipping_fee'],
                    'total_payment' => $summary['total_payment'],
                    'total_weight' => $summary['total_weight'],
                    'courier' => $shipping['courier'],
                    'shipping_service' => $shipping['service'],
                    'shipping_address' => $shipping['address'],
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'payment_expired_at' =>
                        now()->addMinutes(10),
                ]);

            /*
            |--------------------------------------------------------------------------
            | Order Items
            |--------------------------------------------------------------------------
            */
            $this->createOrderItems(
                $order,
                $products
            );

            /*
            |--------------------------------------------------------------------------
            | Reduce Stock
            |--------------------------------------------------------------------------
            */
            $this->inventoryService
                ->decreaseStock(
                    $products
                );

            /*
            |--------------------------------------------------------------------------
            | Midtrans
            |--------------------------------------------------------------------------
            */
            $snap =
                $this->midtransService
                    ->createTransaction(
                        $order
                    );

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */
            $this->paymentService
                ->create(
                    $order,
                    $snap
                );

            /*
            |--------------------------------------------------------------------------
            | History
            |--------------------------------------------------------------------------
            */
            $this->workflowService->initialize(
                $order,
                'Checkout dibuat',
                'system'
            );
            
            return $order->fresh([
                'customer',
                'items',
                'payment',
            ]);
        });
    }

    private function createOrder(
        array $data
    ): Order {
        return Order::create($data);
    }

    private function createOrderItems(
        Order $order,
        Collection $products
    ): void {
        
        foreach ($products as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product']->id,
                'product_name' => $item['product']->name,
                'product_slug' => $item['product']->slug,
                'product_thumbnail' => $item['product']->thumbnail,
                'quantity' => $item['qty'],
                'unit_price' => $item['product']->price,
                'subtotal' => $item['product']->price * $item['qty'],
                'weight' => $item['product']->weight,
                'total_weight' => $item['product']->weight * $item['qty'],
            ]);
        }
    }

    public function cancelOrder(
        Order $order,
        ?string $reason = null,
        ?string $createdBy = null
    ): Order{
        $this->workflowService->validate(
            $order,
            'cancelled'
        );

        return DB::transaction(function () use (

            $order,
            $reason,
            $createdBy

        ) {
            $order->loadMissing([
                'items.product',
            ]);
            /*
            |--------------------------------------------------------------------------
            | Restore Product Stock
            |--------------------------------------------------------------------------
            */
            $products = $order->items->map(function ($item) {
                return [
                    'product' => $item->product,
                    'qty' => $item->quantity,
                ];
            });

            $this->inventoryService
                ->increaseStock(
                    $products
                );

            /*
            |--------------------------------------------------------------------------
            | Update Order
            |--------------------------------------------------------------------------
            */
            $this->workflowService
                ->changeStatus(
                    $order,
                    'cancelled',
                    $reason,
                    $createdBy
                );

            return $order->fresh([
                'payment',
                'items.product',
            ]);

        });
    }

    public function expireOrder(
        Order $order,
        ?string $description = 'Payment expired'
    ): Order {
        $this->workflowService->validate(
            $order,
            'cancelled'
        );
        return DB::transaction(function () use (
            $order,
            $description
        ) {
            $order->update([
                'payment_status'=>'expired',
            ]);
            /*
            |--------------------------------------------------------------------------
            | Load Relation
            |--------------------------------------------------------------------------
            */
            $order->loadMissing([
                'items.product',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Restore Product Stock
            |--------------------------------------------------------------------------
            */
            $products = $order->items->map(function ($item) {
                return [
                    'product' => $item->product,
                    'qty' => $item->quantity,
                ];
            });
            $this->inventoryService
                ->increaseStock($products);

            /*
            |--------------------------------------------------------------------------
            | Update Order
            |--------------------------------------------------------------------------
            */
            $this->workflowService
                ->changeStatus(
                    $order,
                    'cancelled',
                    $description,
                    'system'
                );
            return $order->fresh([
                'items.product',
                'payment',
            ]);
        });
    }

    public function markPaid(
        Order $order,
        ?string $createdBy = 'system'
    ): Order {
        $this->workflowService->validate(
            $order,
            'paid'
        );
        return DB::transaction(function () use (
            $order,
            $createdBy
        ) {
            $order->update([
                'payment_status'=>'paid',
            ]);
            $this->workflowService
                ->changeStatus(
                    $order,
                    'paid',
                    'Payment berhasil diterima',
                    $createdBy
                );
            return $order->fresh([
                'payment',
                'items.product',
            ]);
        });
    }
}