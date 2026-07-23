<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class CheckoutController extends Controller
{
    public function __construct(
        protected \App\Services\Order\OrderService $orderService
    ) {}

    public function store(
        CheckoutRequest $request
    ): JsonResponse {

        $customer = $request->user()->customer;

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        $products = collect(
            $request->products
        )->map(function ($item) {

            return [

                'product' => Product::findOrFail(
                    $item['product_id']
                ),

                'qty' => $item['quantity'],

            ];

        });

        /*
        |--------------------------------------------------------------------------
        | Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = null;

        if ($request->filled('voucher_code')) {

            $voucher = Voucher::where(
                'code',
                $request->voucher_code
            )->first();

        }

        /*
        |--------------------------------------------------------------------------
        | Shipping Address
        |--------------------------------------------------------------------------
        */

        $address = $customer
            ->addresses()
            ->findOrFail(
                $request->shipping['address_id']
            );

        /*
        |--------------------------------------------------------------------------
        | Checkout
        |--------------------------------------------------------------------------
        */

        $order = $this->orderService
            ->checkout(

                customer: $customer,

                products: $products,

                voucher: $voucher,

                shipping: [

                    'courier'
                        => $request->shipping['courier'],

                    'service'
                        => $request->shipping['service'],

                    'address'
                        => $address->toArray(),

                ]

            );

        return response()->json([

            'success' => true,

            'message'
                => 'Checkout berhasil.',

            'data'
                => new OrderResource($order),

        ]);

    }
}