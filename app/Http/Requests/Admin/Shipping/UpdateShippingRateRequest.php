<?php

namespace App\Http\Requests\Admin\Shipping;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingRateRequest extends FormRequest
{
    /**
     * Authorize.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | J&T Cargo
            |--------------------------------------------------------------------------
            */

            'price_per_kg' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Sentral Cargo
            |--------------------------------------------------------------------------
            */

            'first_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'additional_price_per_kg' => [
                'nullable',
                'numeric',
                'min:0',
            ],

        ];
    }

    /**
     * Validation messages.
     */
    public function messages(): array
    {
        return [

            'price_per_kg.numeric' =>
                'Harga per kg harus berupa angka.',

            'price_per_kg.min' =>
                'Harga per kg tidak boleh kurang dari 0.',

            'first_price.numeric' =>
                'Harga dasar harus berupa angka.',

            'first_price.min' =>
                'Harga dasar tidak boleh kurang dari 0.',

            'additional_price_per_kg.numeric' =>
                'Harga tambahan per kg harus berupa angka.',

            'additional_price_per_kg.min' =>
                'Harga tambahan per kg tidak boleh kurang dari 0.',
        ];
    }
}