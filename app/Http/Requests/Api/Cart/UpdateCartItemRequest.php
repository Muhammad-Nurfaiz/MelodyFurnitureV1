<?php

namespace App\Http\Requests\Api\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
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
            | Quantity
            |--------------------------------------------------------------------------
            */

            'quantity' => [
                'required',
                'integer',
                'min:0',
            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'quantity.required'
                => 'Jumlah produk wajib diisi.',

            'quantity.integer'
                => 'Jumlah produk harus berupa angka.',

            'quantity.min'
                => 'Jumlah produk tidak boleh kurang dari 0.',

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