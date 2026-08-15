<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use App\Services\Order\OrderCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ShippingController extends Controller
{
    /**
     * Service pengiriman selalu menggunakan regular.
     */
    private const DEFAULT_SERVICE = 'regular';

    public function __construct(
        protected CartService $cartService,
        protected OrderCalculatorService $calculatorService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Estimate Shipping
    |--------------------------------------------------------------------------
    */

    public function estimate(Request $request): JsonResponse
    {
        $validated = $request->validate([

            'regency_id' => [
                'required',
                'string',
                'exists:regencies,id',
            ],

            'courier' => [
                'required',
                'string',
                'max:50',
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Customer Session
        |--------------------------------------------------------------------------
        */

        $customer = $request->attributes->get('customer');

        if (!$customer) {
            throw new RuntimeException(
                'Customer session tidak ditemukan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Cart
        |--------------------------------------------------------------------------
        */

        $products = $this->cartService->checkoutItems(
            $customer
        );

        /*
        |--------------------------------------------------------------------------
        | Calculate Shipping
        |--------------------------------------------------------------------------
        |
        | Service tidak berasal dari frontend.
        | Sistem selalu menggunakan regular.
        |
        */

        $shipping = $this->calculatorService->calculateShipping(
            products: $products,
            regencyId: $validated['regency_id'],
            courier: $validated['courier'],
            service: self::DEFAULT_SERVICE,
        );

        return response()->json([
            'message' => 'Ongkir berhasil dihitung.',
            'data' => $shipping,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Couriers
    |--------------------------------------------------------------------------
    */

    public function couriers(): JsonResponse
    {
        $couriers = \App\Models\ShippingCourier::query()
            ->where('is_active', true)
            ->whereHas('rates', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
            ]);

        return response()->json([
            'message' => 'Daftar courier berhasil diambil.',
            'data' => $couriers,
        ]);
    }
}