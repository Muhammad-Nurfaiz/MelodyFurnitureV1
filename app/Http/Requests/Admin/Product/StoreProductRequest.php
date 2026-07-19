<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            */

            'category_id' => ['required', 'exists:categories,id'],
            'series_id' => ['nullable', 'exists:series,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'product_detail' => ['nullable', 'string'],

            /*
            |--------------------------------------------------------------------------
            | Specification
            |--------------------------------------------------------------------------
            */

            'dimensions' => ['required', 'string', 'max:100'],
            'seat_height' => ['required', 'string', 'max:50'],
            'load_capacity' => ['required', 'string', 'max:50'],
            'material_details' => ['required', 'string'],

            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            'original_price' => ['required', 'numeric', 'min:0'],
            'discount_price' => [
                'nullable',
                'numeric',
                'min:0',
                'lt:original_price',
            ],
            'discount_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'is_sale' => ['nullable', 'boolean'],

            /*
            |--------------------------------------------------------------------------
            | Stock
            |--------------------------------------------------------------------------
            */

            'ready_stock' => ['required', 'integer', 'min:0'],
            'locked_stock' => ['nullable', 'integer', 'min:0'],

            /*
            |--------------------------------------------------------------------------
            | Statistic
            |--------------------------------------------------------------------------
            */

            'average_rating' => ['nullable', 'numeric', 'between:0,5'],
            'total_sold' => ['nullable', 'integer', 'min:0'],

            /*
            |--------------------------------------------------------------------------
            | Publish
            |--------------------------------------------------------------------------
            */

            'origin_city' => ['nullable', 'string', 'max:100'],
            'video_tutorial_url' => ['nullable', 'url'],

            /*
            |--------------------------------------------------------------------------
            | Temporary Media
            |--------------------------------------------------------------------------
            */

            'temporary_media' => [
                'required',
                'array',
                'min:1',
            ],

            'temporary_media.*' => [
                'uuid',
                'exists:temporary_media,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Media Setting
            |--------------------------------------------------------------------------
            */

            'media_order' => [
                'required',
                'array',
                'min:1',
            ],

            'media_order.*' => [
                'uuid',
            ],

            'main_media' => [
                'required',
                'uuid',
            ],

            'deleted_media' => [
                'nullable',
                'array',
            ],

            'deleted_media.*' => [
                'uuid',
            ],

        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_sale' => $this->boolean('is_sale'),
        ]);
    }

    public function attributes(): array
    {
        return [

            'category_id' => 'Kategori',
            'series_id' => 'Series',
            'name' => 'Nama Produk',
            'slug' => 'Slug',
            'description' => 'Deskripsi',
            'product_detail' => 'Detail Produk',

            'dimensions' => 'Dimensi',
            'seat_height' => 'Tinggi Dudukan',
            'load_capacity' => 'Kapasitas Beban',
            'material_details' => 'Material',

            'original_price' => 'Harga Normal',
            'discount_price' => 'Harga Diskon',
            'discount_percentage' => 'Persentase Diskon',

            'ready_stock' => 'Ready Stock',
            'locked_stock' => 'Locked Stock',

            'average_rating' => 'Average Rating',
            'total_sold' => 'Total Terjual',

            'origin_city' => 'Kota Asal',

            'temporary_media' => 'Media Produk',

            'media_order' => 'Urutan Media',

            'main_media' => 'Thumbnail',

            'deleted_media' => 'Media yang dihapus',

        ];
    }

    public function messages(): array
    {
        return [

            'temporary_media.required' =>
                'Minimal upload satu gambar produk.',

            'temporary_media.min' =>
                'Minimal upload satu gambar produk.',

            'temporary_media.*.exists' =>
                'Media temporary tidak ditemukan.',

            'temporary_media.*.uuid' =>
                'Format media tidak valid.',

            'media_order.required' =>
                'Urutan media tidak boleh kosong.',

            'media_order.array' =>
                'Format urutan media tidak valid.',

            'main_media.required' =>
                'Silakan pilih thumbnail produk.',

            'main_media.uuid' =>
                'Thumbnail yang dipilih tidak valid.',

            'deleted_media.array' =>
                'Media yang dihapus tidak valid.',

            'discount_price.lt' =>
                'Harga diskon harus lebih kecil dari harga normal.',

        ];
    }
}