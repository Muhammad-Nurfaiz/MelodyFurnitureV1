<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            'update',
            $this->product
        );
    }

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            */

            'category_id' => [
                'required',
                'exists:categories,id',
            ],

            'series_id' => [
                'nullable',
                'exists:series,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'sku' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z0-9-]+$/',
                'unique:products,sku,' . $this->product->id,
            ],

            'description' => [
                'required',
                'string',
            ],

            'product_detail' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Specification
            |--------------------------------------------------------------------------
            */

            'dimensions' => [
                'required',
                'string',
                'max:100',
            ],

            'weight' => [
                'required',
                'numeric',
                'min:0',
            ],

            'packing_weight' => [
                'required',
                'numeric',
                'min:0',
            ],

            'load_capacity' => [
                'required',
                'string',
                'max:50',
            ],

            'assembly_required' => [
                'nullable',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            'original_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'discount_price' => [
                'nullable',
                'numeric',
                'min:0',
                'lt:original_price',
            ],

            /*
            |--------------------------------------------------------------------------
            | Stock
            |--------------------------------------------------------------------------
            */

            'ready_stock' => [
                'required',
                'integer',
                'min:0',
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
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Publish
            |--------------------------------------------------------------------------
            */

            'video_tutorial_url' => [
                'nullable',
                'url',
            ],

            /*
            |--------------------------------------------------------------------------
            | Temporary Media
            |--------------------------------------------------------------------------
            */

            'temporary_media' => [
                'nullable',
                'array',
            ],

            'temporary_media.*' => [
                'uuid',
                'exists:temporary_media,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Media Manager
            |--------------------------------------------------------------------------
            */

            'media_order' => [
                'nullable',
                'array',
            ],

            'media_order.*' => [
                'uuid',
            ],

            'main_media' => [
                'nullable',
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
            'assembly_required' => $this->boolean('assembly_required'),
        ]);
    }

    public function attributes(): array
    {
        return [

            'category_id' => 'Kategori',
            'series_id' => 'Series',

            'name' => 'Nama Produk',
            'sku' => 'SKU Produk',
            'description' => 'Deskripsi',
            'product_detail' => 'Detail Produk',

            'dimensions' => 'Dimensi',
            'weight' => 'Berat Produk',
            'packing_weight' => 'Berat Setelah Packing',
            'load_capacity' => 'Kapasitas Beban',
            'assembly_required' => 'Perlu Dirakit',

            'original_price' => 'Harga Normal',
            'discount_price' => 'Harga Diskon',

            'ready_stock' => 'Ready Stock',

            'average_rating' => 'Average Rating',
            'total_sold' => 'Total Terjual',

            'temporary_media' => 'Media Produk',
            'media_order' => 'Urutan Media',
            'main_media' => 'Thumbnail',
            'deleted_media' => 'Media yang dihapus',
        ];
    }

    public function messages(): array
    {
        return [

            'media_order.array' =>
                'Format urutan media tidak valid.',

            'media_order.*.uuid' =>
                'Format ID media tidak valid.',

            'main_media.uuid' =>
                'Thumbnail yang dipilih tidak valid.',

            'deleted_media.array' =>
                'Media yang dihapus tidak valid.',

            'discount_price.lt' =>
                'Harga diskon harus lebih kecil dari harga normal.',

            'sku.required' =>
                'SKU produk wajib diisi.',

            'sku.regex' =>
                'SKU hanya boleh berisi huruf besar A-Z, angka, dan tanda strip (-), tanpa spasi atau simbol lainnya.',

            'sku.unique' =>
                'SKU produk sudah digunakan.',
        ];
    }
}