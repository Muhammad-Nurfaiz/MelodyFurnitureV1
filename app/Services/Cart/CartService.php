<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Product;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\ProductVariant;
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

    public function addItem(
        Customer $customer,
        Product $product,
        int $quantity,
        ?ProductVariant $variant = null
    ): Cart {
        $this->validateQuantity($quantity);

        if (!$variant && $product->variants()->where('is_active', true)->exists()) {
            throw new RuntimeException(
                'Varian produk wajib dipilih.'
            );
        }

        if ($variant) {
            if ($variant->product_id !== $product->id) {
                throw new RuntimeException(
                    'Varian produk tidak sesuai dengan produk.'
                );
            }

            if (!$variant->is_active) {
                throw new RuntimeException(
                    'Varian produk tidak tersedia.'
                );
            }

            $availableStock = $variant->ready_stock;
        } else {
            $availableStock = $product->ready_stock;
        }

        if ($availableStock < 1) {
            throw new RuntimeException('Produk sedang habis.');
        }

        return DB::transaction(function () use (
            $customer,
            $product,
            $quantity,
            $variant
        ) {
            $cart = $this->get($customer);

            $item = $this->findItem(
                $cart,
                $product,
                $variant
            );

            $newQuantity = $item
                ? $item->quantity + $quantity
                : $quantity;

            $availableStock = $variant
                ? $variant->ready_stock
                : $product->ready_stock;

            if ($newQuantity > $availableStock) {
                throw new RuntimeException(
                    'Stok produk tidak mencukupi.'
                );
            }

            if ($item) {
                $item->increment('quantity', $quantity);
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
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

    public function updateItem(CartItem $item, int $quantity): Cart
    {
        if ($quantity <= 0) {
            return $this->removeItem($item);
        }

        $this->validateQuantity($quantity);

        $availableStock = $item->product_variant_id
            ? $item->productVariant->ready_stock
            : $item->product->ready_stock;

        if ($quantity > $availableStock) {
            throw new RuntimeException(
                'Stok produk tidak mencukupi.'
            );
        }

        $item->update([
            'quantity' => $quantity,
        ]);

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
    | Remove Items By Ids
    |--------------------------------------------------------------------------
    |
    | Menghapus hanya item tertentu dari cart berdasarkan array ID.
    | Digunakan setelah checkout parsial (selected_item_ids) agar
    | item yang tidak dipilih tetap tersimpan.
    |
    */

    public function removeItemsByIds(Cart $cart, array $itemIds): void {
        $cart->items()
            ->whereIn('id', $itemIds)
            ->delete();
    }

    public function checkoutItems(Customer $customer): Collection
    {
        $cart = $this->get($customer);

        if ($cart->items->isEmpty()) {
            throw new RuntimeException('Cart kosong.');
        }

        return $cart->items->load([
            'product.thumbnail',
            'product.specification',
            'productVariant',
        ]);
    }

    public function checkoutSelectedItems(
        Customer $customer,
        array $selectedItemIds
    ): Collection {
        $cart = $this->get($customer);

        if ($cart->items->isEmpty()) {
            throw new RuntimeException('Cart kosong.');
        }

        $selectedItems = $cart->items
            ->filter(
                fn ($item) => in_array(
                    $item->id,
                    $selectedItemIds
                )
            );

        if ($selectedItems->isEmpty()) {
            throw new RuntimeException(
                'Item yang dipilih tidak ditemukan di keranjang.'
            );
        }

        return $selectedItems->load([
            'product.thumbnail',
            'product.specification',
            'productVariant',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Find Item
    |--------------------------------------------------------------------------
    */

    private function findItem(
        Cart $cart,
        Product $product,
        ?ProductVariant $variant = null
    ): ?CartItem {
        $query = $cart
            ->items()
            ->where('product_id', $product->id);

        if ($variant) {
            $query->where('product_variant_id', $variant->id);
        } else {
            $query->whereNull('product_variant_id');
        }

        return $query->first();
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

    private function refreshCart(Cart $cart): Cart
    {
        return $cart->fresh([
            'customer',
            'items.product.thumbnail',
            'items.product.specification',
            'items.productVariant',
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