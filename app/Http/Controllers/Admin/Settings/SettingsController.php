<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Models\HeroSlide;
use App\Models\PromoBanner;
use App\Services\Admin\AdminSettingsService;
use Illuminate\Http\Request;
use RuntimeException;
use App\Services\Admin\HeroSlideService;
use Illuminate\Support\Facades\DB;

class SettingsController extends AdminController
{
    public function __construct(
        protected AdminSettingsService $settingsService,
        protected HeroSlideService $heroSlideService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $settings = $this->settingsService->getSettings();

        $heroSlides = HeroSlide::query()
            ->orderBy('sort_order')
            ->get();

        $promoBanners = PromoBanner::query()
            ->orderBy('sort_order')
            ->get();

        return view(
            'admin.modules.settings.index',
            compact(
                'settings',
                'heroSlides',
                'promoBanners'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Store
    |--------------------------------------------------------------------------
    */

    public function updateStore(Request $request)
    {
        $validated = $request->validate([
            'store_name' => [
                'required',
                'string',
                'max:150',
            ],

            'store_description' => [
                'nullable',
                'string',
            ],

            'instagram_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'facebook_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'tiktok_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'youtube_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'whatsapp_url' => [
                'nullable',
                'url',
                'max:255',
            ],
        ]);

        $settings = $this->settingsService->getSettings();

        $this->settingsService->updateStore(
            setting: $settings,
            data: $validated,
        );

        return $this->success(
            'admin.settings.index',
            'Informasi toko berhasil diperbarui.'
        );
    }

    public function updateBranding(Request $request)
    {
        $validated = $request->validate([
            'type' => [
                'required',
                'in:logo,favicon',
            ],

            'temporary_media_id' => [
                'required',
                'string',
                'exists:temporary_media,id',
            ],
        ]);

        $settings = $this->settingsService->getSettings();

        if ($validated['type'] === 'logo') {

            $this->settingsService->updateLogo(
                setting: $settings,
                temporaryMediaId: $validated['temporary_media_id'],
            );

            return response()->json([
                'success' => true,
                'message' => 'Logo berhasil diperbarui.',
            ]);
        }

        $this->settingsService->updateFavicon(
            setting: $settings,
            temporaryMediaId: $validated['temporary_media_id'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Favicon berhasil diperbarui.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Hero
    |--------------------------------------------------------------------------
    */

    public function storeHero(Request $request)
    {
        $validated = $request->validate([
            'temporary_media_id' => [
                'required',
                'string',
                'exists:temporary_media,id',
            ],

            'eyebrow' => [
                'nullable',
                'string',
                'max:100',
            ],

            'title' => [
                'required',
                'string',
                'max:200',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'button_text' => [
                'nullable',
                'string',
                'max:100',
            ],

            'button_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $this->heroSlideService->create(
            data: $validated,
            temporaryMediaId: $validated['temporary_media_id'],
        );

        return $this->success(
            'admin.settings.index',
            'Hero slide berhasil ditambahkan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Hero
    |--------------------------------------------------------------------------
    */

    public function updateHero(
        Request $request,
        HeroSlide $heroSlide
    ) {

        $validated = $request->validate([
            'temporary_media_id' => [
                'nullable',
                'uuid',
            ],

            'eyebrow' => [
                'nullable',
                'string',
                'max:100',
            ],

            'title' => [
                'required',
                'string',
                'max:200',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'button_text' => [
                'nullable',
                'string',
                'max:100',
            ],

            'button_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $this->settingsService->updateHeroSlide(
            slide: $heroSlide,
            data: $validated,
            temporaryMediaId: $validated['temporary_media_id'] ?? null,
        );

        return $this->success(
            'admin.settings.index',
            'Hero slide berhasil diperbarui.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Hero
    |--------------------------------------------------------------------------
    */

    public function destroyHero(
        HeroSlide $heroSlide
    ) {

        $this->settingsService->deleteHeroSlide(
            slide: $heroSlide
        );

        return $this->success(
            'admin.settings.index',
            'Hero slide berhasil dihapus.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Promo Banner
    |--------------------------------------------------------------------------
    */

    public function storePromo(
        Request $request
    ) {

        $validated = $request->validate([
            'temporary_media_id' => [
                'required',
                'uuid',
            ],

            'url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'alt' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $this->settingsService->createPromoBanner(
            data: $validated,
            temporaryMediaId: $validated['temporary_media_id'],
        );

        return $this->success(
            'admin.settings.index',
            'Promo banner berhasil ditambahkan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Promo Banner
    |--------------------------------------------------------------------------
    */

    public function updatePromo(
        Request $request,
        PromoBanner $promoBanner
    ) {

        $validated = $request->validate([
            'temporary_media_id' => [
                'nullable',
                'uuid',
            ],

            'url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'alt' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $this->settingsService->updatePromoBanner(
            banner: $promoBanner,
            data: $validated,
            temporaryMediaId: $validated['temporary_media_id'] ?? null,
        );

        return $this->success(
            'admin.settings.index',
            'Promo banner berhasil diperbarui.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Promo Banner
    |--------------------------------------------------------------------------
    */

    public function destroyPromo(
        PromoBanner $promoBanner
    ) {

        $this->settingsService->deletePromoBanner(
            banner: $promoBanner
        );

        return $this->success(
            'admin.settings.index',
            'Promo banner berhasil dihapus.'
        );
    }

    public function sortPromo(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'uuid', 'exists:promo_banners,id'],
        ]);

        if (count($validated['ids']) !== PromoBanner::count()) {

            return response()->json([
                'success' => false,
                'message' => 'Data banner tidak valid.',
            ], 422);

        }

        try {

            DB::transaction(function () use ($validated) {

                foreach ($validated['ids'] as $index => $id) {

                    PromoBanner::query()
                        ->whereKey($id)
                        ->update([
                            'sort_order' => $index + 1,
                        ]);

                }

            });

            return response()->json([
                'success' => true,
                'message' => 'Urutan banner berhasil diperbarui.',
            ]);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui urutan banner.',
            ], 500);

        }
    }
}