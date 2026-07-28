<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Product;
use App\Models\CartItem;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CartService
{
    /*
    |--------------------------------------------------------------------------
    | Get Cart
    |--------------------------------------------------------------------------
    */

    public function get(Customer $customer): Cart {
        $cart = Cart::firstOrCreate(['customer_id' => $customer->id,]);
        return $this->refreshCart($cart);
    }

    /*
    |--------------------------------------------------------------------------
    | Add Item
    |--------------------------------------------------------------------------
    */

    public function addItem(Customer $customer, Product $product, int $quantity): Cart {
        if ($product->ready_stock < 1) {
            throw new RuntimeException('Produk sedang habis.');
        }
        $this->validateQuantity($quantity);
        return DB::transaction(function () use ($customer,$product,$quantity) {
            $cart = $this->get($customer);
            $item = $this->findItem($cart,$product);
            $newQuantity = $item ? $item->quantity + $quantity : $quantity;

            if ($newQuantity > $product->ready_stock) {
                throw new RuntimeException('Stok produk tidak mencukupi.');
            }

            if ($item) {
                $item->increment('quantity', $quantity);
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);
            }
            return $this->refreshCart($cart);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Item
    |--------------------------------------------------------------------------
    */

    public function updateItem(CartItem $item, int $quantity): Cart {
        if ($quantity <= 0) {
            return $this->removeItem($item);
        }
        $this->validateQuantity($quantity);
        if ($quantity > $item->product->ready_stock) {
            throw new RuntimeException('Stok produk tidak mencukupi.');
        }
        $item->update(['quantity' => $quantity,]);
        return $this->refreshCart($item->cart);
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Item
    |--------------------------------------------------------------------------
    */

    public function removeItem(CartItem $item): Cart {
        $cart = $item->cart;
        $item->delete();
        return $this->refreshCart($cart);
    }

    /*
    |--------------------------------------------------------------------------
    | Clear Cart
    |--------------------------------------------------------------------------
    */

    public function clear(Customer $customer): Cart {
        $cart = $this->get($customer);
        $this->clearCart($cart);
        return $this->refreshCart($cart);
    }

    public function clearCart(Cart $cart): void {
        $cart->items()->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Checkout Items
    |--------------------------------------------------------------------------
    */

    public function checkoutItems(Customer $customer): Collection {
        $cart = $this->get($customer);
        if ($cart->items->isEmpty()) {
            throw new RuntimeException('Cart kosong.');
        }
        return $cart->items->load([
            'product.thumbnail',
            'product.specification',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Find Item
    |--------------------------------------------------------------------------
    */

    private function findItem(Cart $cart, Product $product): ?CartItem {
        return $cart
            ->items()
            ->where('product_id', $product->id)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Refresh Cart
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

    private function refreshCart(Cart $cart): Cart {
        return $cart->fresh([
            'customer',
            'items.product.thumbnail',
            'items.product.specification',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Quantity
    |--------------------------------------------------------------------------
    */

    private function validateQuantity(int $quantity): void {
        if ($quantity < 1) {
            throw new RuntimeException('Jumlah minimal 1.');
        }
    }
}