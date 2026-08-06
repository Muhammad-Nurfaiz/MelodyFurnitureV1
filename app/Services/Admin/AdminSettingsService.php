<?php

namespace App\Services\Admin;

use App\Models\HeroSlide;
use App\Models\PromoBanner;
use App\Models\Setting;
use App\Services\Media\TemporaryMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AdminSettingsService
{
    public function __construct(
        protected TemporaryMediaService $temporaryMediaService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Get Settings
    |--------------------------------------------------------------------------
    */

    public function getSettings(): Setting
    {
        return Setting::query()->firstOrCreate(
            [],
            [
                'store_name' => 'Melody Furniture',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Store Settings
    |--------------------------------------------------------------------------
    */

    public function updateStore(
        Setting $setting,
        array $data,
    ): Setting {

        return DB::transaction(function () use (
            $setting,
            $data
        ) {

            $setting->update([
                'store_name'        => $data['store_name'],
                'store_description' => $data['store_description'] ?? null,

                'instagram_url'     => $data['instagram_url'] ?? null,
                'facebook_url'      => $data['facebook_url'] ?? null,
                'tiktok_url'        => $data['tiktok_url'] ?? null,
                'youtube_url'       => $data['youtube_url'] ?? null,
                'whatsapp_url'      => $data['whatsapp_url'] ?? null,
            ]);

            return $setting->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Store Logo
    |--------------------------------------------------------------------------
    */

    public function updateLogo(
        Setting $setting,
        string $temporaryMediaId,
    ): Setting {

        return DB::transaction(function () use (
            $setting,
            $temporaryMediaId
        ) {

            $oldPath = $setting->store_logo;

            $newPath = $this->temporaryMediaService->moveTo(
                $temporaryMediaId,
                'settings/logo'
            );

            $setting->update([
                'store_logo' => $newPath,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Delete Old Media
            |--------------------------------------------------------------------------
            */

            if (
                $oldPath &&
                $oldPath !== $newPath
            ) {
                Storage::disk('public')->delete($oldPath);
            }

            return $setting->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Store Favicon
    |--------------------------------------------------------------------------
    */

    public function updateFavicon(
        Setting $setting,
        string $temporaryMediaId,
    ): Setting {

        return DB::transaction(function () use (
            $setting,
            $temporaryMediaId
        ) {

            $oldPath = $setting->store_favicon;

            $newPath = $this->temporaryMediaService->moveTo(
                $temporaryMediaId,
                'settings/favicon'
            );

            $setting->update([
                'store_favicon' => $newPath,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Delete Old Media
            |--------------------------------------------------------------------------
            */

            if (
                $oldPath &&
                $oldPath !== $newPath
            ) {
                Storage::disk('public')->delete($oldPath);
            }

            return $setting->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Hero Slides
    |--------------------------------------------------------------------------
    */

    public function createHeroSlide(
        array $data,
        string $temporaryMediaId,
    ): HeroSlide {

        return DB::transaction(function () use (
            $data,
            $temporaryMediaId
        ) {

            $image = $this->temporaryMediaService->moveTo(
                $temporaryMediaId,
                'settings/hero'
            );

            $sortOrder = (
                HeroSlide::query()->max('sort_order') ?? 0
            ) + 1;

            return HeroSlide::create([
                'image'        => $image,
                'eyebrow'      => $data['eyebrow'] ?? null,
                'title'        => $data['title'],
                'description'  => $data['description'] ?? null,
                'button_text'  => $data['button_text'] ?? null,
                'button_url'   => $data['button_url'] ?? null,
                'sort_order'   => $sortOrder,
                'is_active'    => $data['is_active'] ?? true,
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Hero Slide
    |--------------------------------------------------------------------------
    */

    public function updateHeroSlide(
        HeroSlide $slide,
        array $data,
        ?string $temporaryMediaId = null,
    ): HeroSlide {

        return DB::transaction(function () use (
            $slide,
            $data,
            $temporaryMediaId
        ) {

            $oldPath = $slide->image;

            $newPath = $oldPath;

            if ($temporaryMediaId) {

                $newPath = $this->temporaryMediaService->moveTo(
                    $temporaryMediaId,
                    'settings/hero'
                );
            }

            $slide->update([
                'image'        => $newPath,
                'eyebrow'      => $data['eyebrow'] ?? null,
                'title'        => $data['title'],
                'description'  => $data['description'] ?? null,
                'button_text'  => $data['button_text'] ?? null,
                'button_url'   => $data['button_url'] ?? null,
                'is_active'    => $data['is_active'] ?? true,
            ]);

            if (
                $temporaryMediaId &&
                $oldPath &&
                $oldPath !== $newPath
            ) {
                Storage::disk('public')->delete($oldPath);
            }

            return $slide->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Hero Slide
    |--------------------------------------------------------------------------
    */

    public function deleteHeroSlide(
        HeroSlide $slide
    ): void {

        DB::transaction(function () use ($slide) {

            $path = $slide->image;

            $slide->delete();

            if ($path) {
                Storage::disk('public')->delete($path);
            }

            $this->normalizeHeroSortOrder();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Hero Sort Order
    |--------------------------------------------------------------------------
    */

    public function normalizeHeroSortOrder(): void
    {
        $slides = HeroSlide::query()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        foreach ($slides as $index => $slide) {

            $slide->update([
                'sort_order' => $index + 1,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Promo Banners
    |--------------------------------------------------------------------------
    */

    public function createPromoBanner(
        array $data,
        string $temporaryMediaId,
    ): PromoBanner {

        return DB::transaction(function () use (
            $data,
            $temporaryMediaId
        ) {

            $image = $this->temporaryMediaService->moveTo(
                $temporaryMediaId,
                'settings/promo-banners'
            );

            $sortOrder = (
                PromoBanner::query()->max('sort_order') ?? 0
            ) + 1;

            return PromoBanner::create([
                'image'      => $image,
                'url'        => $data['url'] ?? null,
                'alt'        => $data['alt'] ?? null,
                'sort_order' => $sortOrder,
                'is_active'  => $data['is_active'] ?? true,
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Promo Banner
    |--------------------------------------------------------------------------
    */

    public function updatePromoBanner(
        PromoBanner $banner,
        array $data,
        ?string $temporaryMediaId = null,
    ): PromoBanner {

        return DB::transaction(function () use (
            $banner,
            $data,
            $temporaryMediaId
        ) {

            $oldPath = $banner->image;

            $newPath = $oldPath;

            if ($temporaryMediaId) {

                $newPath = $this->temporaryMediaService->moveTo(
                    $temporaryMediaId,
                    'settings/promo-banners'
                );
            }

            $banner->update([
                'image'      => $newPath,
                'url'        => $data['url'] ?? null,
                'alt'        => $data['alt'] ?? null,
                'is_active'  => $data['is_active'] ?? true,
            ]);

            if (
                $temporaryMediaId &&
                $oldPath &&
                $oldPath !== $newPath
            ) {
                Storage::disk('public')->delete($oldPath);
            }

            return $banner->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Promo Banner
    |--------------------------------------------------------------------------
    */

    public function deletePromoBanner(
        PromoBanner $banner
    ): void {

        DB::transaction(function () use ($banner) {

            $path = $banner->image;

            $banner->delete();

            if ($path) {
                Storage::disk('public')->delete($path);
            }

            $this->normalizePromoSortOrder();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Promo Sort Order
    |--------------------------------------------------------------------------
    */

    public function normalizePromoSortOrder(): void
    {
        $banners = PromoBanner::query()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        foreach ($banners as $index => $banner) {

            $banner->update([
                'sort_order' => $index + 1,
            ]);
        }
    }
}