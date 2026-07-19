<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->product);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'exists:categories,id'
            ],

            'series_id' => [
                'nullable',
                'exists:series,id'
            ],

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'slug' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ],

            'description' => [
                'required',
                'string'
            ],

            'product_detail' => [
                'nullable',
                'string'
            ],

            /*
            |--------------------------------------------------------------------------
            | Specification
            |--------------------------------------------------------------------------
            */

            'dimensions' => [
                'required',
                'string',
                'max:100'
            ],

            'seat_height' => [
                'required',
                'string',
                'max:50'
            ],

            'load_capacity' => [
                'required',
                'string',
                'max:50'
            ],

            'material_details' => [
                'required',
                'string'
            ],

            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            'original_price' => [
                'required',
                'numeric',
                'min:0'
            ],

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
                'max:100'
            ],

            'is_sale' => [
                'nullable',
                'boolean'
            ],

            /*
            |--------------------------------------------------------------------------
            | Stock
            |--------------------------------------------------------------------------
            */

            'ready_stock' => [
                'required',
                'integer',
                'min:0'
            ],

            'locked_stock' => [
                'nullable',
                'integer',
                'min:0'
            ],

            /*
            |--------------------------------------------------------------------------
            | Statistic
            |--------------------------------------------------------------------------
            */

            'average_rating' => [
                'nullable',
                'numeric',
                'between:0,5',
            ],

            'total_sold' => [
                'nullable',
                'integer',
                'min:0'
            ],

            /*
            |--------------------------------------------------------------------------
            | Publish
            |--------------------------------------------------------------------------
            */

            'origin_city' => [
                'nullable',
                'string',
                'max:100'
            ],

            'video_tutorial_url' => [
                'nullable',
                'url'
            ],

            /*
            |--------------------------------------------------------------------------
            | Media
            |--------------------------------------------------------------------------
            */

            // Gallery
            'gallery' => [
                'nullable',
                'array',
            ],

            'gallery.*' => [
                \Illuminate\Validation\Rules\File::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max(2048),
            ],

            // Media Manager
            'media_order' => [
                'nullable',
                'array',
            ],

            'media_order.*' => [
                'string',
            ],

            'main_media' => [
                'nullable',
                'string',
            ],

            'deleted_media' => [
                'nullable',
                'array',
            ],

            'deleted_media.*' => [
                'string',
            ],
        ];
    }

    /**
     * Prepare data before validation
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_sale' => $this->boolean('is_sale'),
        ]);
    }

    /**
     * Custom Attribute Names
     */
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
            'gallery.*' => 'Galeri',
            'media_order' => 'Urutan Media',
            'main_media' => 'Thumbnail',
            'deleted_media' => 'Media yang dihapus',

        ];
    }

    public function messages(): array
    {
        return [

            'gallery.*.image' => 'Gallery harus berupa gambar.',

            'gallery.*.max' => 'Ukuran gallery maksimal 2 MB.',

            'media_order.array' => 'Format urutan media tidak valid.',

            'deleted_media.array' => 'Media yang dihapus tidak valid.',

            'discount_price.lt' => 'Harga diskon harus lebih kecil dari harga normal.',

        ];
    }
}
