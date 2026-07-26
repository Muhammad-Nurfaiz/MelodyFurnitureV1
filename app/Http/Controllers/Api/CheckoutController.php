<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\Order\OrderService;
use App\Services\Cart\CartService;
use App\Services\Voucher\VoucherService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected CartService $cartService,
        protected VoucherService $voucherService,
    ) {}

    /**
     * Checkout
     */
    public function store(CheckoutRequest $request): JsonResponse {
        $payload = $request->payload();
        $customer = $request->attributes->get('customer');

        /*
        |--------------------------------------------------------------------------
        | Cart
        |--------------------------------------------------------------------------
        */

        $products = $this->cartService
            ->checkoutItems($customer);

        /*
        |--------------------------------------------------------------------------
        | Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = null;
        if (!empty($payload['voucher_code'])) {
            $voucher = $this->voucherService
                ->findByCode(
                    $payload['voucher_code']
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */

        $shipping = [
            'courier' => $payload['courier'],
            'service' => $payload['service'],
            'address' => $payload['shipping_address'],
        ];

        $order = $this->orderService
            ->checkout(
                customer: $customer,
                products: $products,
                voucher: $voucher,
                shipping: $shipping,
            );

        return response()->json([
            'message' => 'Checkout berhasil.',
            'data' => new OrderResource($order),
        ], 201);
    }
}