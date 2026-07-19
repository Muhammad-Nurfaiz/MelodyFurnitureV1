<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\ProductMedia;
use App\Services\Product\ProductMediaService;

class ProductMediaController extends Controller
{
    public function destroy(
        ProductMedia $media,
        ProductMediaService $service
    ) {
        $service->delete($media);

        return back()->with(
            'success',
            'Gambar berhasil dihapus.'
        );
    }
}