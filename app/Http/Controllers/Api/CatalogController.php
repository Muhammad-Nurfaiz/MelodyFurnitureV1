<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Series;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    /**
     * Get categories for product catalog filter.
     */
    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
            ]);

        return response()->json([
            'message' => 'Daftar kategori berhasil diambil.',
            'data' => $categories,
        ]);
    }

    /**
     * Get series for product catalog filter.
     */
    public function series(): JsonResponse
    {
        $series = Series::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
            ]);

        return response()->json([
            'message' => 'Daftar series berhasil diambil.',
            'data' => $series,
        ]);
    }
}