<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\Order\OrderService;
use App\Services\Cart\CartService;
use App\Services\Voucher\VoucherService;
use App\Services\Customer\CustomerService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected CartService $cartService,
        protected VoucherService $voucherService,
        protected CustomerService $customerService,
    ) {}

    /**
     * Checkout
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        $payload = $request->payload();

        /*
        |--------------------------------------------------------------------------
        | Cart Customer
        |--------------------------------------------------------------------------
        |
        | Customer dari guest token hanya digunakan sebagai pemilik cart.
        | Data customer checkout TIDAK boleh mengubah record ini.
        |
        */

        $cartCustomer = $request->attributes->get('customer');

        /*
        |--------------------------------------------------------------------------
        | Cart
        |--------------------------------------------------------------------------
        */

        $products = $this->cartService
            ->checkoutItems($cartCustomer);

        /*
        |--------------------------------------------------------------------------
        | Checkout Customer
        |--------------------------------------------------------------------------
        |
        | Customer sebenarnya ditentukan berdasarkan nomor telepon.
        |
        | - Phone sudah ada  → gunakan customer existing.
        | - Phone belum ada  → buat customer baru.
        |
        */

        $customer = $this->customerService->resolveCheckoutCustomer([
            'name'    => $payload['name'],
            'email'   => $payload['email'],
            'phone'   => $payload['phone'],
            'address' => $payload['shipping_address']['address'],
        ]);

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
                cartCustomer: $cartCustomer,
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