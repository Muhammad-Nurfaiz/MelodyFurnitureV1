<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

    /*
    |--------------------------------------------------------------------------
    | Estimate Shipping — All Couriers
    |--------------------------------------------------------------------------
    |
    | Menghitung ongkir semua kurir aktif sekaligus untuk
    | regency tujuan dan berat total keranjang.
    |
    | Digunakan frontend untuk merender radio button ekspedisi
    | beserta harga masing-masing dalam satu request.
    |
    */

    public function estimateAll(Request $request): JsonResponse
    {
        $validated = $request->validate([

            'regency_id' => [
                'required',
                'string',
                'exists:regencies,id',
            ],

            'items' => [
                'nullable',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required_with:items',
                'string',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required_with:items',
                'integer',
                'min:1',
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Customer Session
        |--------------------------------------------------------------------------
        */

        $customer = $request->attributes->get('customer');

        if (!$customer) {
            throw new \RuntimeException(
                'Customer session tidak ditemukan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        |
        | Jika items dikirim, berarti Direct Checkout.
        | Jika tidak, gunakan Cart Checkout seperti sebelumnya.
        |
        */

        if (!empty($validated['items'])) {

            $productIds = collect($validated['items'])
                ->pluck('product_id')
                ->unique()
                ->values();

            $productMap = Product::query()
                ->with('specification')
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $products = collect($validated['items'])
                ->map(function (array $item) use ($productMap) {

                    $product = $productMap->get(
                        $item['product_id']
                    );

                    if (!$product) {
                        throw new RuntimeException(
                            'Produk direct checkout tidak ditemukan.'
                        );
                    }

                    return (object) [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                    ];
                });

        } else {

            $products = $this->cartService->checkoutItems(
                $customer
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate All Couriers
        |--------------------------------------------------------------------------
        */

        $result = $this->calculatorService->calculateShippingAllCouriers(
            products:  $products,
            regencyId: $validated['regency_id'],
        );

        return response()->json([
            'message' => 'Estimasi ongkir semua kurir berhasil dihitung.',
            'data'    => $result,
        ]);
    }
}