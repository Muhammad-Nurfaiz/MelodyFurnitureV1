<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\PromoBanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;

class HomeContentController extends Controller
{
    /**
     * Get active hero slides.
     */
    public function heroes(): JsonResponse
    {
        $heroes = HeroSlide::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get()
            ->map(function (HeroSlide $hero) {
                return [
                    'id' => $hero->id,
                    'image' => $this->imageUrl($hero->image),
                    'eyebrow' => $hero->eyebrow,
                    'title' => $hero->title,
                    'description' => $hero->description,
                    'button_text' => $hero->button_text,
                    'button_url' => $hero->button_url,
                    'sort_order' => $hero->sort_order,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $heroes,
        ]);
    }

    /**
     * Get active promo banners.
     */
    public function promos(): JsonResponse
    {
        $promos = PromoBanner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get()
            ->map(function (PromoBanner $promo) {
                return [
                    'id' => $promo->id,
                    'image' => $this->imageUrl($promo->image),
                    'url' => $promo->url,
                    'alt' => $promo->alt,
                    'sort_order' => $promo->sort_order,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $promos,
        ]);
    }

    /**
     * Get store social media URLs.
     */
    public function socialMedia(): JsonResponse
    {
        $settings = Setting::query()->first();

        return response()->json([
            'success' => true,
            'data' => [
                'instagram_url' => $settings?->instagram_url,
                'facebook_url' => $settings?->facebook_url,
                'tiktok_url' => $settings?->tiktok_url,
                'youtube_url' => $settings?->youtube_url,
                'whatsapp_url' => $settings?->whatsapp_url,
            ],
        ]);
    }

    /**
     * Convert storage path to public URL.
     */
    protected function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}