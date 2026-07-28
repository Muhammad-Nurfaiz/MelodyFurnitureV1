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
            | Customer Identity
            |--------------------------------------------------------------------------
            */

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:100',
            ],

            'phone' => [
                'required',
                'string',
                'max:30',
            ],

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

            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',

            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 100 karakter.',

            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.max' => 'Nomor telepon maksimal 30 karakter.',

            'courier.required' => 'Kurir wajib dipilih.',
            'service.required' => 'Layanan pengiriman wajib dipilih.',

            'shipping_address.required' => 'Alamat pengiriman wajib diisi.',

            'shipping_address.recipient_name.required'
                => 'Nama penerima wajib diisi.',

            'shipping_address.phone.required'
                => 'Nomor telepon penerima wajib diisi.',

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
        return $this->validated();
    }
}