<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DirectCheckoutRequest extends FormRequest
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
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'uuid',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

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
                'nullable',
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
            | Courier
            |--------------------------------------------------------------------------
            */

            'courier' => [
                'required',
                'string',
                'max:50',
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

            'shipping_address.regency_id' => [
                'required',
                'string',
                'exists:regencies,id',
            ],

            'shipping_address.address' => [
                'required',
                'string',
                'max:500',
            ],

            'shipping_address.area' => [
                'required',
                'string',
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
            'items.required' =>
                'Produk wajib diisi.',

            'items.min' =>
                'Minimal 1 produk.',

            'items.*.product_id.required' =>
                'ID produk wajib diisi.',

            'items.*.product_id.exists' =>
                'Produk tidak ditemukan.',

            'items.*.quantity.required' =>
                'Jumlah produk wajib diisi.',

            'items.*.quantity.min' =>
                'Jumlah minimal 1.',

            'name.required' =>
                'Nama wajib diisi.',

            'email.email' =>
                'Format email tidak valid.',

            'phone.required' =>
                'Nomor telepon wajib diisi.',

            'courier.required' =>
                'Kurir wajib dipilih.',

            'shipping_address.required' =>
                'Alamat pengiriman wajib diisi.',

            'shipping_address.recipient_name.required' =>
                'Nama penerima wajib diisi.',

            'shipping_address.phone.required' =>
                'Nomor telepon penerima wajib diisi.',

            'shipping_address.regency_id.required' =>
                'Kabupaten/Kota wajib dipilih.',

            'shipping_address.regency_id.exists' =>
                'Kabupaten/Kota tidak valid.',

            'shipping_address.address.required' =>
                'Alamat wajib diisi.',

            'shipping_address.area.required' =>
                'Kecamatan wajib diisi.',

            'shipping_address.postal_code.required' =>
                'Kode pos wajib diisi.',
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