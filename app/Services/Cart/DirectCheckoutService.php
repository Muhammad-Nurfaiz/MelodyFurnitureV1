<?php

namespace App\Services\Cart;

use App\Models\Product;
use App\Models\CartItem;
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
                    'variants',
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
            | Resolve Variant
            |--------------------------------------------------------------------------
            */

            $productVariant = null;

            if (!empty($item['product_variant_id'])) {
                $productVariant = $product->variants
                    ->firstWhere('id', $item['product_variant_id']);

                if (!$productVariant) {
                    throw new RuntimeException(
                        "Varian produk {$product->name} tidak ditemukan."
                    );
                }

                if (!$productVariant->is_active) {
                    throw new RuntimeException(
                        "Varian {$productVariant->name} pada produk {$product->name} tidak aktif."
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Stock Check
            |--------------------------------------------------------------------------
            */

            $availableStock = $productVariant
                ? (int) $productVariant->ready_stock
                : (int) $product->ready_stock;

            if ($availableStock < 1) {
                if ($productVariant) {
                    throw new RuntimeException(
                        "Stok varian {$productVariant->name} pada produk {$product->name} sedang habis."
                    );
                }

                throw new RuntimeException(
                    "Produk {$product->name} sedang habis."
                );
            }

            if ($item['quantity'] > $availableStock) {
                if ($productVariant) {
                    throw new RuntimeException(
                        "Stok varian {$productVariant->name} pada produk {$product->name} tidak mencukupi. " .
                        "Tersedia: {$availableStock}."
                    );
                }

                throw new RuntimeException(
                    "Stok produk {$product->name} tidak mencukupi. " .
                    "Tersedia: {$availableStock}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Build Item Object
            |--------------------------------------------------------------------------
            |
            | Struktur dibuat agar kompatibel dengan OrderService.
            |
            */

            $cartItem = new CartItem([
                'product_variant_id' => $productVariant?->id,
                'quantity' => (int) $item['quantity'],
            ]);

            $cartItem->setRelation('product', $product);

            if ($productVariant) {
                $cartItem->setRelation('productVariant', $productVariant);
            }

            $resolved->push($cartItem);
        }

        if ($resolved->isEmpty()) {
            throw new RuntimeException(
                'Tidak ada produk yang dapat diproses.'
            );
        }

        return $resolved;
    }
}
