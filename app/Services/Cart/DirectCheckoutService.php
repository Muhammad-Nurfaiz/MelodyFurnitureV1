<?php

namespace App\Services\Cart;

use App\Models\Product;
use Illuminate\Support\Collection;
use RuntimeException;

class DirectCheckoutService
{
    /*
    |--------------------------------------------------------------------------
    | Resolve Items
    |--------------------------------------------------------------------------
    |
    | Mengubah array [{product_id, quantity}] dari payload frontend
    | menjadi Collection dengan struktur yang sama seperti output
    | CartService@checkoutItems() agar bisa diproses langsung oleh
    | OrderService@checkout() tanpa modifikasi.
    |
    | Setiap item akan divalidasi:
    | - Produk harus ada
    | - Produk harus memiliki spesifikasi (untuk berat)
    | - Stok produk mencukupi untuk qty yang diminta
    |
    */

    public function resolveItems(array $items): Collection
    {
        $resolved = collect();

        foreach ($items as $item) {

            $product = Product::query()
                ->with([
                    'thumbnail',
                    'specification',
                ])
                ->find($item['product_id']);

            /*
            |--------------------------------------------------------------------------
            | Product Exists
            |--------------------------------------------------------------------------
            */

            if (!$product) {
                throw new RuntimeException(
                    "Produk dengan ID {$item['product_id']} tidak ditemukan."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Stock Check
            |--------------------------------------------------------------------------
            */

            if ($product->ready_stock < 1) {
                throw new RuntimeException(
                    "Produk {$product->name} sedang habis."
                );
            }

            if ($item['quantity'] > $product->ready_stock) {
                throw new RuntimeException(
                    "Stok produk {$product->name} tidak mencukupi. " .
                    "Tersedia: {$product->ready_stock}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Build Item Object
            |--------------------------------------------------------------------------
            |
            | Dibuat sebagai object anonim agar strukturnya sama
            | persis dengan CartItem yang di-load dari database.
            |
            */

            $resolved->push((object) [
                'product'  => $product,
                'quantity' => (int) $item['quantity'],
            ]);
        }

        if ($resolved->isEmpty()) {
            throw new RuntimeException('Tidak ada produk yang dapat diproses.');
        }

        return $resolved;
    }
}
