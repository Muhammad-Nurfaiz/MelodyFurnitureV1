<?php

namespace App\Services\Inventory;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProductInventoryService
{
    /*
    |--------------------------------------------------------------------------
    | Validate Stock
    |--------------------------------------------------------------------------
    */
    public function validateStock(Collection $products): void
    {
        /*
        |--------------------------------------------------------------------------
        | Lock Product Rows
        |--------------------------------------------------------------------------
        |
        | Non-variant product tetap menggunakan row products.
        |
        | Variant product menggunakan row product_variants.
        | ID diurutkan agar urutan locking konsisten dan mengurangi
        | risiko deadlock ketika beberapa checkout berjalan bersamaan.
        |
        */

        $productIds = $products
            ->filter(fn ($item) => !$this->hasVariant($item))
            ->map(fn ($item) => $item->product->id)
            ->unique()
            ->sort()
            ->values();

        $lockedProducts = Product::query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $variantIds = $products
            ->filter(fn ($item) => $this->hasVariant($item))
            ->map(fn ($item) => $item->product_variant_id)
            ->unique()
            ->sort()
            ->values();

        $lockedVariants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($products as $item) {
            $qty = $item->quantity;

            /*
            |--------------------------------------------------------------------------
            | Variant
            |--------------------------------------------------------------------------
            */

            if ($this->hasVariant($item)) {
                $variant = $lockedVariants->get(
                    $item->product_variant_id
                );

                if (!$variant) {
                    throw ValidationException::withMessages([
                        'stock' => 'Varian produk tidak ditemukan.',
                    ]);
                }

                if ($variant->product_id !== $item->product->id) {
                    throw ValidationException::withMessages([
                        'stock' => 'Varian produk tidak sesuai dengan produk.',
                    ]);
                }

                if ($variant->ready_stock < $qty) {
                    throw ValidationException::withMessages([
                        'stock' => "Stok varian {$variant->name} tidak mencukupi.",
                    ]);
                }

                /*
                | Gunakan instance variant yang sudah di-lock
                | untuk proses checkout berikutnya.
                */
                $item->setRelation('productVariant', $variant);

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Non-variant Product
            |--------------------------------------------------------------------------
            */

            $product = $lockedProducts->get($item->product->id);

            if (!$product) {
                throw ValidationException::withMessages([
                    'stock' => 'Produk tidak ditemukan.',
                ]);
            }

            // Gunakan instance product yang sudah di-lock untuk seluruh
            // proses checkout berikutnya, termasuk calculate() dan decreaseStock().
            $item->product = $product;

            if ($product->ready_stock < $qty) {
                throw ValidationException::withMessages([
                    'stock' => "Stok {$product->name} tidak mencukupi.",
                ]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Decrease Stock
    |--------------------------------------------------------------------------
    */
    public function decreaseStock(Collection $products): Collection
    {
        $lowStockProductIds = collect();

        foreach ($products as $item) {
            $qty = $item->quantity;

            /*
            |--------------------------------------------------------------------------
            | Variant
            |--------------------------------------------------------------------------
            */

            if ($this->hasVariant($item)) {
                $variant = $this->getVariant($item);

                $variant->decrement(
                    'ready_stock',
                    $qty
                );

                $variant->increment(
                    'locked_stock',
                    $qty
                );

                if ($variant->fresh()->ready_stock <= 3) {
                    $lowStockProductIds->push(
                        $item->product->id
                    );
                }

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Non-variant Product
            |--------------------------------------------------------------------------
            */

            $product = $item->product;

            $product->decrement(
                'ready_stock',
                $qty
            );

            $product->increment(
                'locked_stock',
                $qty
            );

            if ($product->fresh()->ready_stock <= 3) {
                $lowStockProductIds->push(
                    $product->id
                );
            }
        }

        return $lowStockProductIds
            ->unique()
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Confirm Sale
    |--------------------------------------------------------------------------
    */
    public function confirmSale(Collection $products): void
    {
        foreach ($products as $item) {
            $qty = $item->quantity;

            /*
            |--------------------------------------------------------------------------
            | Variant
            |--------------------------------------------------------------------------
            */

            if ($this->hasVariant($item)) {
                $variant = $this->getVariant($item);

                $variant->decrement(
                    'locked_stock',
                    $qty
                );

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Non-variant Product
            |--------------------------------------------------------------------------
            */

            /** @var Product $product */
            $product = $item->product;

            $product->decrement(
                'locked_stock',
                $qty
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Release Locked Stock
    |--------------------------------------------------------------------------
    */
    public function releaseLockedStock(Collection $products): void
    {
        foreach ($products as $item) {
            $qty = $item->quantity;

            /*
            |--------------------------------------------------------------------------
            | Variant
            |--------------------------------------------------------------------------
            */

            if ($this->hasVariant($item)) {
                $variant = $this->getVariant($item);

                $variant->decrement(
                    'locked_stock',
                    $qty
                );

                $variant->increment(
                    'ready_stock',
                    $qty
                );

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Non-variant Product
            |--------------------------------------------------------------------------
            */

            /** @var Product $product */
            $product = $item->product;

            $product->decrement(
                'locked_stock',
                $qty
            );

            $product->increment(
                'ready_stock',
                $qty
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Increase Stock
    |--------------------------------------------------------------------------
    */
    public function increaseStock(Collection $products): void
    {
        foreach ($products as $item) {
            $qty = $item->quantity;

            /*
            |--------------------------------------------------------------------------
            | Variant
            |--------------------------------------------------------------------------
            */

            if ($this->hasVariant($item)) {
                $variant = $this->getVariant($item);

                $variant->increment(
                    'ready_stock',
                    $qty
                );

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Non-variant Product
            |--------------------------------------------------------------------------
            */

            /** @var Product $product */
            $product = $item->product;

            $product->increment(
                'ready_stock',
                $qty
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function hasVariant($item): bool
    {
        return !empty($item->product_variant_id);
    }

    private function getVariant($item): ProductVariant
    {
        $variant = $item->relationLoaded('productVariant')
            ? $item->productVariant
            : null;

        if ($variant) {
            return $variant;
        }

        $variant = ProductVariant::query()
            ->find($item->product_variant_id);

        if (!$variant) {
            throw ValidationException::withMessages([
                'stock' => 'Varian produk tidak ditemukan.',
            ]);
        }

        if ($variant->product_id !== $item->product->id) {
            throw ValidationException::withMessages([
                'stock' => 'Varian produk tidak sesuai dengan produk.',
            ]);
        }

        return $variant;
    }
}