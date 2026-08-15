<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use MadeByClowd\Nusantara\Models\Province;
use MadeByClowd\Nusantara\Models\Regency;

class LocationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Provinces
    |--------------------------------------------------------------------------
    */

    public function provinces(): JsonResponse
    {
        $provinces = Province::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return response()->json([
            'message' => 'Daftar provinsi berhasil diambil.',
            'data' => $provinces,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Regencies
    |--------------------------------------------------------------------------
    */

    public function regencies(
        string $provinceId
    ): JsonResponse {

        $regencies = Regency::query()
            ->where('province_id', $provinceId)
            ->orderBy('name')
            ->get([
                'id',
                'province_id',
                'name',
            ]);

        return response()->json([
            'message' => 'Daftar kabupaten/kota berhasil diambil.',
            'data' => $regencies,
        ]);
    }
}