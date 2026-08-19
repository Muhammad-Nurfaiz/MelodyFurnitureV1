<?php

namespace App\Services\Inventory;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProductInventoryService
{
    /*
    |--------------------------------------------------------------------------
    | Validate Stock
    |--------------------------------------------------------------------------
    */
    public function validateStock(
        Collection $products
    ): void {
        foreach ($products as $item) {
            $product = $item->product;
            $qty = $item->quantity;
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
    public function decreaseStock(
        Collection $products
    ): Collection {
        $lowStockProductIds = collect();

        foreach ($products as $item) {
            /** @var Product $product */
            $product = $item->product;
            $qty = $item->quantity;

            $product->decrement(
                'ready_stock',
                $qty
            );

            if (
                $product->fresh()->ready_stock
                <= 3
            ) {
                $lowStockProductIds->push(
                    $product->id
                );
            }
        }

        return $lowStockProductIds->unique()->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Increase Stock
    |--------------------------------------------------------------------------
    */
    public function increaseStock(
        Collection $products
    ): void {
        foreach ($products as $item) {
            /** @var Product $product */
            $product = $item->product;
            $qty = $item->quantity;
            $product->increment(
                'ready_stock',
                $qty
            );
        }
    }
}