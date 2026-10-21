<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MetaCatalogFeedController extends Controller
{
    public function __invoke(): Response
    {
        $products = Product::query()
            ->with([
                'thumbnail',
                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with('media'),
            ])
            ->orderBy('id')
            ->get();

        $handle = fopen('php://temp', 'r+');

        // BOM membantu aplikasi spreadsheet membaca UTF-8.
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'id',
            'title',
            'description',
            'availability',
            'condition',
            'link',
            'image_link',
            'brand',
            'price',
        ]);

        foreach ($products as $product) {
            $variants = $product->variants;

            if ($variants->isNotEmpty()) {
                foreach ($variants as $variant) {
                    $price = $variant->discount_price
                        ?? $variant->original_price
                        ?? $product->discount_price
                        ?? $product->original_price;

                    $image = $variant->media ?? $product->thumbnail;

                    $this->writeProductRow(
                        $handle,
                        id: $variant->sku ?: $variant->id,
                        title: $product->name . ' - ' . $variant->name,
                        description: $product->description,
                        stock: (int) $variant->ready_stock,
                        link: $this->productLink($product->slug),
                        image: $image,
                        price: $price,
                    );
                }

                continue;
            }

            $this->writeProductRow(
                $handle,
                id: $product->sku ?: $product->id,
                title: $product->name,
                description: $product->description,
                stock: (int) $product->ready_stock,
                link: $this->productLink($product->slug),
                image: $product->thumbnail,
                price: $product->discount_price
                    ?? $product->original_price,
            );
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function writeProductRow(
        $handle,
        string $id,
        string $title,
        ?string $description,
        int $stock,
        string $link,
        $image,
        $price,
    ): void {
        // Produk tanpa gambar utama tidak dimasukkan ke feed.
        // Meta membutuhkan image_link yang valid.
        $imageUrl = $this->imageUrl($image);

        if (!$imageUrl) {
            return;
        }

        fputcsv($handle, [
            $id,
            $title,
            trim(strip_tags($description ?? '')),
            $stock > 0 ? 'in stock' : 'out of stock',
            'new',
            $link,
            $imageUrl,
            'Melody Furniture',
            number_format((float) $price, 2, '.', '') . ' IDR',
        ]);
    }

    private function productLink(string $slug): string
    {
        return rtrim(config('app.frontend_url'), '/')
            . '/produk/'
            . rawurlencode($slug);
    }

    private function imageUrl($image): ?string
    {
        if (!$image || !$image->media_url) {
            return null;
        }

        $path = $image->media_url;

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
