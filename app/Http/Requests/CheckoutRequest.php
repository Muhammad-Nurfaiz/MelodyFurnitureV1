<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'products' => [
                'required',
                'array',
                'min:1',
            ],

            'products.*.product_id' => [
                'required',
                'uuid',
                'exists:products,id',
            ],

            'products.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'voucher_code' => [
                'nullable',
                'string',
            ],

            'shipping' => [
                'required',
                'array',
            ],

            'shipping.courier' => [
                'required',
                'string',
            ],

            'shipping.service' => [
                'required',
                'string',
            ],

            'shipping.address_id' => [
                'required',
                'uuid',
            ],

        ];
    }
    
    public function messages(): array
    {
        return [

            'products.required' =>
                'Produk harus dipilih.',

            'products.min' =>
                'Minimal terdapat satu produk.',

            'products.*.product_id.exists' =>
                'Produk tidak ditemukan.',

            'products.*.quantity.min' =>
                'Jumlah produk minimal satu.',

            'shipping.required' =>
                'Data pengiriman wajib diisi.',

        ];
    }
}
