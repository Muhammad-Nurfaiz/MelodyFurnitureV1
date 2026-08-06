<?php

namespace App\Services\Admin;

use App\Models\HeroSlide;
use App\Services\Media\TemporaryMediaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class HeroSlideService
{
    public function __construct(
        protected TemporaryMediaService $temporaryMediaService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data,
        string $temporaryMediaId
    ): HeroSlide {

        return DB::transaction(function () use (
            $data,
            $temporaryMediaId
        ) {

            $imagePath = $this->temporaryMediaService->moveTo(
                $temporaryMediaId,
                'settings/hero'
            );

            return HeroSlide::create([
                'image' => $imagePath,

                'eyebrow' => $data['eyebrow'] ?? null,

                'title' => $data['title'],

                'description' => $data['description'] ?? null,

                'button_text' => $data['button_text'] ?? null,

                'button_url' => $data['button_url'] ?? null,

                'sort_order' => (
                    HeroSlide::query()->max('sort_order') ?? 0
                ) + 1,

                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        HeroSlide $heroSlide,
        array $data,
        ?string $temporaryMediaId = null
    ): HeroSlide {

        return DB::transaction(function () use (
            $heroSlide,
            $data,
            $temporaryMediaId
        ) {

            $oldImage = $heroSlide->image;

            if ($temporaryMediaId) {

                $newImage = $this->temporaryMediaService->moveTo(
                    $temporaryMediaId,
                    'settings/hero'
                );

                $data['image'] = $newImage;

            }

            $heroSlide->update([
                'image' => $data['image'] ?? $heroSlide->image,

                'eyebrow' => $data['eyebrow'] ?? null,

                'title' => $data['title'],

                'description' => $data['description'] ?? null,

                'button_text' => $data['button_text'] ?? null,

                'button_url' => $data['button_url'] ?? null,

                'sort_order' => $data['sort_order'] ?? 0,

                'is_active' => $data['is_active'] ?? false,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Delete old image
            |--------------------------------------------------------------------------
            */

            if (
                $temporaryMediaId &&
                $oldImage &&
                $oldImage !== $heroSlide->image
            ) {

                Storage::disk('public')
                    ->delete($oldImage);
            }

            return $heroSlide->refresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function delete(
        HeroSlide $heroSlide
    ): void {

        DB::transaction(function () use ($heroSlide) {

            $image = $heroSlide->image;

            $heroSlide->delete();

            if ($image) {

                Storage::disk('public')
                    ->delete($image);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Reorder
    |--------------------------------------------------------------------------
    */

    public function reorder(
        array $orderedIds
    ): void {

        DB::transaction(function () use ($orderedIds) {

            foreach ($orderedIds as $index => $id) {

                HeroSlide::query()
                    ->whereKey($id)
                    ->update([
                        'sort_order' => $index + 1,
                    ]);
            }
        });
    }
}