<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\TemporaryMedia;

class ProductMediaService
{
    /*
    |--------------------------------------------------------------------------
    | Upload Gallery
    |--------------------------------------------------------------------------
    */

    public function attachTemporaryMedia(
        Product $product,
        array $temporaryMediaIds,
        array $mediaOrder = [],
        ?string $mainMedia = null
    ): void {
        $sort = $product->media()->max('sort_order') ?? 0;

        $temporaryMedia = TemporaryMedia::query()
            ->whereIn('id', $temporaryMediaIds)
            ->get();
        $idMap = [];
        
        foreach ($temporaryMedia as $temp) {

            $sort++;

            $newPath = "products/{$product->id}/gallery/" . basename($temp->path);

            if (
                Storage::disk('public')->exists($temp->path)
            ) {
                Storage::disk('public')->move(
                    $temp->path,
                    $newPath
                );
            }
            
            $mediaType = str_starts_with(
                $temp->mime_type,
                'video/'
            )
                ? 'video'
                : 'image';

            $productMedia = $product->media()->create([
                'media_type'    => $mediaType,
                'media_url'     => $newPath,
                'thumbnail_url' => $newPath,
                'alt_text'      => $product->name,
                'is_main'       => false,
                'sort_order'    => $sort,
            ]);
            $idMap[$temp->id] = $productMedia->id;
            $temp->delete();
        }
        if (!empty($mediaOrder)) {

            $ordered = [];

            foreach ($mediaOrder as $id) {

                $ordered[] = $idMap[$id] ?? $id;

            }

            $this->updateSortOrder(
                $product,
                $ordered
            );

        }
        if ($mainMedia) {

            $this->setMainMedia(

                $product,

                $idMap[$mainMedia] ?? $mainMedia

            );

        }
        else {

            $this->ensureMainMedia($product);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Sync Media
    |--------------------------------------------------------------------------
    |
    | Mengatur urutan media sekaligus menentukan thumbnail utama.
    |
    */

    public function syncMedia(
        Product $product,
        array $orderedIds = [],
        ?string $mainMediaId = null,
    ): void {

        DB::transaction(function () use (
            $product,
            $orderedIds,
            $mainMediaId
        ) {

            /*
            |--------------------------------------------------------------------------
            | Update Sort Order
            |--------------------------------------------------------------------------
            */

            foreach ($orderedIds as $index => $id) {

                $product
                    ->media()
                    ->where('id', $id)
                    ->update([
                        'sort_order' => $index + 1,
                    ]);

            }

            /*
            |--------------------------------------------------------------------------
            | Update Main Media
            |--------------------------------------------------------------------------
            */

            if ($mainMediaId) {

                $product
                    ->media()
                    ->update([
                        'is_main' => false,
                    ]);

                $product
                    ->media()
                    ->where('id', $mainMediaId)
                    ->update([
                        'is_main' => true,
                    ]);

            }

            /*
            |--------------------------------------------------------------------------
            | Safety
            |--------------------------------------------------------------------------
            */

            $this->ensureMainMedia($product);

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Delete Selected Media
    |--------------------------------------------------------------------------
    */

    public function deleteMedia(
        Product $product,
        array $mediaIds
    ): void {

        $media = $product
            ->media()
            ->whereIn('id', $mediaIds)
            ->get();

        foreach ($media as $item) {

            Storage::disk('public')->delete($item->media_url);

            $item->delete();
        }

        $this->ensureMainMedia($product);

    }

    /*
    |--------------------------------------------------------------------------
    | Set Main Media
    |--------------------------------------------------------------------------
    */

    public function setMainMedia(
        Product $product,
        string $mediaId
    ): void {

        DB::transaction(function () use (
            $product,
            $mediaId
        ) {

            $product
                ->media()
                ->update([
                    'is_main' => false,
                ]);

            $product
                ->media()
                ->where('id', $mediaId)
                ->update([
                    'is_main' => true,
                ]);

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Update Sort Order
    |--------------------------------------------------------------------------
    */

    public function updateSortOrder(
        Product $product,
        array $orderedIds
    ): void {

        foreach ($orderedIds as $index => $id) {

            $product
                ->media()
                ->where('id', $id)
                ->update([

                    'sort_order' => $index + 1,

                ]);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Delete Single Media
    |--------------------------------------------------------------------------
    */

    public function delete(ProductMedia $media): void
    {
        DB::transaction(function () use ($media) {

            $product = $media->product;

            Storage::disk('public')->delete($media->media_url);

            $media->delete();

            $this->ensureMainMedia($product);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Ensure Main Media Exists
    |--------------------------------------------------------------------------
    */

    protected function ensureMainMedia(
        Product $product
    ): void {

        if (

            $product
                ->media()
                ->where('is_main', true)
                ->exists()

        ) {

            return;

        }

        $first = $product
            ->media()
            ->orderBy('sort_order')
            ->first();

        if ($first) {

            $first->update([

                'is_main' => true,

            ]);

        }

    }

    public function deleteAll(Product $product): void
    {
        $media = $product->media()->get();

        foreach ($media as $item) {

            Storage::disk('public')->delete($item->media_url);

            if (
                $item->thumbnail_url &&
                $item->thumbnail_url !== $item->media_url
            ) {
                Storage::disk('public')->delete(
                    $item->thumbnail_url
                );
            }

            $item->delete();
        }
    }
}