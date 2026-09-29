<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            'create',
            \App\Models\Product::class
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
                'unique:products,sku',
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

            'is_sale' => [
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

            'variants_enabled' => [
                'required',
                'boolean',
            ],

            'ready_stock' => [
                'required_if:variants_enabled,false',
                'nullable',
                'integer',
                'min:0',
            ],

            'variants' => [
                'required_if:variants_enabled,true',
                'array',
                'min:1',
            ],

            'variants.*.name' => [
                'required',
                'string',
                'max:100',
                'distinct',
            ],

            'variants.*.sku' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z0-9-]+$/',
                'distinct',
                'unique:product_variants,sku',
            ],

            'variants.*.original_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'variants.*.discount_price' => [
                'nullable',
                'numeric',
                'min:0',
                'lt:variants.*.original_price',
            ],

            'variants.*.media_id' => [
                'required',
                'uuid',
                'exists:temporary_media,id',
            ],

            'variants.*.ready_stock' => [
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
        $variants = collect($this->input('variants', []))
            ->filter(function ($variant) {
                return filled($variant['name'] ?? '')
                    || filled($variant['sku'] ?? '')
                    || filled($variant['original_price'] ?? '')
                    || filled($variant['discount_price'] ?? '')
                    || filled($variant['media_id'] ?? '')
                    || filled($variant['ready_stock'] ?? '');
            })
            ->values()
            ->all();

        $this->merge([
            'is_sale' => $this->boolean('is_sale'),
            'assembly_required' => $this->boolean('assembly_required'),
            'variants_enabled' => $this->boolean('variants_enabled'),
            'variants' => $variants,
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

            'variants.*.name' => 'Nama Varian',
            'variants.*.sku' => 'SKU Varian',
            'variants.*.original_price' => 'Harga Asli Varian',
            'variants.*.discount_price' => 'Harga Diskon Varian',
            'variants.*.media_id' => 'Foto Varian',
            'variants.*.ready_stock' => 'Ready Stock Varian',

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

            'temporary_media.required' =>
                'Minimal upload satu media produk.',

            'temporary_media.min' =>
                'Minimal upload satu media produk.',

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
                
            'sku.required' =>
                'SKU produk wajib diisi.',

            'sku.regex' =>
                'SKU hanya boleh berisi huruf besar A-Z, angka, dan tanda strip (-), tanpa spasi atau simbol lainnya.',

            'sku.unique' =>
                'SKU produk sudah digunakan.',

            'variants.*.sku.required' =>
                'SKU varian wajib diisi.',

            'variants.*.sku.regex' =>
                'SKU varian hanya boleh berisi huruf besar A-Z, angka, dan tanda strip (-), tanpa spasi atau simbol lainnya.',

            'variants.*.sku.distinct' =>
                'SKU varian tidak boleh sama.',

            'variants.*.sku.unique' =>
                'SKU varian sudah digunakan.',

            'variants.*.original_price.required' =>
                'Harga asli varian wajib diisi.',

            'variants.*.discount_price.lt' =>
                'Harga diskon varian harus lebih kecil dari harga asli varian.',

            'variants.*.media_id.required' =>
                'Foto varian wajib dipilih.',

            'variants.*.media_id.exists' =>
                'Foto varian yang dipilih tidak ditemukan.',

            'variants.*.ready_stock.required' =>
                'Stok varian wajib diisi.',
        ];
    }
}