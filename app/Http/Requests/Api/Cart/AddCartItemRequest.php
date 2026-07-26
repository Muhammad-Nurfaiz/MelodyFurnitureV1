<?php

namespace App\Http\Requests\Api\Cart;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    /**
     * Authorize
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            */

            'product_id' => [
                'required',
                'uuid',
                'exists:products,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'product_id.required'
                => 'Produk wajib dipilih.',

            'product_id.exists'
                => 'Produk tidak ditemukan.',

            'quantity.required'
                => 'Jumlah produk wajib diisi.',

            'quantity.integer'
                => 'Jumlah produk harus berupa angka.',

            'quantity.min'
                => 'Jumlah minimal adalah 1.',

        ];
    }

    /**
     * Safe Payload
     */
    public function payload(): array
    {
        return $this->validated();
    }
}