<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            | Voucher
            |--------------------------------------------------------------------------
            */

            'voucher_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            'courier' => [
                'required',
                'string',
                'max:50',
            ],

            'service' => [
                'required',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Shipping Address
            |--------------------------------------------------------------------------
            */

            'shipping_address' => [
                'required',
                'array',
            ],

            'shipping_address.recipient_name' => [
                'required',
                'string',
                'max:100',
            ],

            'shipping_address.phone' => [
                'required',
                'string',
                'max:30',
            ],

            'shipping_address.address' => [
                'required',
                'string',
                'max:500',
            ],

            'shipping_address.city' => [
                'required',
                'string',
                'max:100',
            ],

            'shipping_address.province' => [
                'required',
                'string',
                'max:100',
            ],

            'shipping_address.postal_code' => [
                'required',
                'string',
                'max:10',
            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'courier.required'
                => 'Kurir wajib dipilih.',

            'service.required'
                => 'Layanan pengiriman wajib dipilih.',

            'shipping_address.required'
                => 'Alamat pengiriman wajib diisi.',

            'shipping_address.recipient_name.required'
                => 'Nama penerima wajib diisi.',

            'shipping_address.phone.required'
                => 'Nomor telepon wajib diisi.',

            'shipping_address.address.required'
                => 'Alamat wajib diisi.',

            'shipping_address.city.required'
                => 'Kota wajib diisi.',

            'shipping_address.province.required'
                => 'Provinsi wajib diisi.',

            'shipping_address.postal_code.required'
                => 'Kode pos wajib diisi.',

        ];
    }

    /**
     * Safe Payload
     */
    public function payload(): array
    {
        $request->payload();
    }
}