<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class VoucherCheckRequest extends FormRequest
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
            | Voucher Code
            |--------------------------------------------------------------------------
            */

            'code' => [
                'required',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Subtotal
            |--------------------------------------------------------------------------
            |
            | Dibutuhkan untuk menghitung estimasi potongan harga
            | dan validasi minimum pembelian voucher.
            |
            */

            'subtotal' => [
                'required',
                'numeric',
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
            'code.required'     => 'Kode voucher wajib diisi.',
            'code.max'          => 'Kode voucher maksimal 100 karakter.',
            'subtotal.required' => 'Subtotal wajib diisi.',
            'subtotal.numeric'  => 'Subtotal harus berupa angka.',
            'subtotal.min'      => 'Subtotal tidak boleh negatif.',
        ];
    }
}
