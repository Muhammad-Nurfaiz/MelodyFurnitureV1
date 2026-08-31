<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductCardResource;
use App\Http\Resources\Product\ProductDetailResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display product catalog.
     *
     * Supports:
     * - search
     * - category filter
     * - series filter
     * - sale filter
     * - cursor pagination
     */
    public function index(Request $request)
    {
        $products = Product::query()
            ->with([
                'thumbnail',
                'category',
                'series',
            ])

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            ->search($request->input('search'))

            /*
            |--------------------------------------------------------------------------
            | Filter
            |--------------------------------------------------------------------------
            */

            ->category($request->input('category'))
            ->series($request->input('series'))

            ->when(
                $request->boolean('sale'),
                fn ($query) => $query->sale()
            )

            /*
            |--------------------------------------------------------------------------
            | Ordering
            |--------------------------------------------------------------------------
            |
            | Gunakan ID sebagai secondary ordering agar cursor pagination
            | memiliki urutan yang stabil.
            |
            */

            ->orderByDesc('created_at')
            ->orderByDesc('id')

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            |
            | 20 product per request.
            |
            */

            ->cursorPaginate(20)
            ->withQueryString();

        return ProductCardResource::collection($products);
    }

    /**
     * Display product detail.
     */
    public function show(string $slug): ProductDetailResource
    {
        $product = Product::query()
            ->with([
                'category',
                'series',
                'specification',
                'media' => fn ($query) =>
                    $query->orderBy('sort_order'),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProductDetailResource($product);
    }

    /**
     * Display recommended products.
     *
     * Priority:
     * 1. Same series
     * 2. Same category
     * 3. Similar price
     *
     * Maximum 4 products.
     */
    public function recommendations(string $slug)
    {
        $product = Product::query()
            ->select([
                'id',
                'category_id',
                'series_id',
                'original_price',
                'discount_price',
                'is_sale',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $price = $product->is_sale && !is_null($product->discount_price)
            ? (float) $product->discount_price
            : (float) $product->original_price;

        $products = Product::query()
            ->with([
                'thumbnail',
                'category',
                'series',
            ])
            ->whereKeyNot($product->id)

            /*
            |--------------------------------------------------------------------------
            | Priority 1 - Same Series
            |--------------------------------------------------------------------------
            */

            ->orderByRaw(
                'CASE
                    WHEN series_id IS NOT NULL
                        AND series_id = ? THEN 0
                    ELSE 1
                END',
                [$product->series_id]
            )

            /*
            |--------------------------------------------------------------------------
            | Priority 2 - Same Category
            |--------------------------------------------------------------------------
            */

            ->orderByRaw(
                'CASE
                    WHEN category_id = ? THEN 0
                    ELSE 1
                END',
                [$product->category_id]
            )

            /*
            |--------------------------------------------------------------------------
            | Priority 3 - Similar Price
            |--------------------------------------------------------------------------
            */

            ->orderByRaw(
                'ABS(
                    (
                        CASE
                            WHEN is_sale = 1
                                AND discount_price IS NOT NULL
                            THEN discount_price
                            ELSE original_price
                        END
                    ) - ?
                )',
                [$price]
            )

            ->orderByDesc('total_sold')
            ->limit(4)
            ->get();

        return ProductCardResource::collection($products);
    }
}