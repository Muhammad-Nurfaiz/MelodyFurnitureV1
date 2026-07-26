<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Cart\AddCartItemRequest;
use App\Http\Requests\Api\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Get Cart
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): CartResource
    {
        $cart = $this->cartService->get(
            $request->attributes->get('customer')
        );

        return CartResource::make($cart);
    }

    /*
    |--------------------------------------------------------------------------
    | Add Item
    |--------------------------------------------------------------------------
    */

    public function store(
        AddCartItemRequest $request
    ): JsonResponse {

        $product = Product::findOrFail(
            $request->product_id
        );

        $cart = $this->cartService->addItem(
            customer: $request->attributes->get('customer'),
            product: $product,
            quantity: $request->quantity,
        );

        return response()->json([
            'message' => 'Produk berhasil ditambahkan ke cart.',
            'data' => CartResource::make($cart),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Item
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateCartItemRequest $request,
        string $item
    ): JsonResponse {

        $customer = $request->attributes->get('customer');

        $cartItem = $this->cartService->findItemByCustomer(
            $customer,
            $item
        );

        $cart = $this->cartService->updateItem(
            $cartItem,
            $request->quantity
        );

        return response()->json([
            'message' => 'Cart berhasil diperbarui.',
            'data' => CartResource::make($cart),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Item
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        string $item
    ): JsonResponse {

        $customer = $request->attributes->get('customer');

        $cartItem = $this->cartService->findItemByCustomer(
            $customer,
            $item
        );

        $cart = $this->cartService->removeItem($cartItem);

        return response()->json([
            'message' => 'Produk berhasil dihapus dari cart.',
            'data' => CartResource::make($cart),
        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | Clear Cart
    |--------------------------------------------------------------------------
    */

    public function clear(
        Request $request
    ): JsonResponse {

        $cart = $this->cartService->clear(
            $request->attributes->get('customer')
        );

        return response()->json([
            'message' => 'Cart berhasil dikosongkan.',
            'data' => CartResource::make($cart),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Find Item By Customer
    |--------------------------------------------------------------------------
    */

    public function findItemByCustomer(
        Customer $customer,
        string $itemId
    ): CartItem {

        return CartItem::query()

            ->whereKey($itemId)

            ->whereHas('cart', function ($query) use ($customer) {

                $query->where(
                    'customer_id',
                    $customer->id
                );

            })

            ->firstOrFail();

    }
}