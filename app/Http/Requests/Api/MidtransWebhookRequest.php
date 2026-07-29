<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class MidtransWebhookRequest extends FormRequest
{
    /**
     * Midtrans tidak menggunakan authentication Laravel.
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
            | Required
            |--------------------------------------------------------------------------
            */

            'transaction_id' => [
                'required',
                'string',
            ],

            'transaction_status' => [
                'required',
                'in:pending,capture,settlement,deny,cancel,expire,refund',
            ],

            'order_id' => [
                'required',
                'string',
            ],

            'status_code' => [
                'required',
                'string',
            ],

            'gross_amount' => [
                'required',
                'string',
            ],

            'signature_key' => [
                'required',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Optional
            |--------------------------------------------------------------------------
            */

            'payment_type' => [
                'nullable',
                'string',
            ],

            'fraud_status' => [
                'nullable',
                'string',
            ],

            'transaction_time' => [
                'nullable',
                'date',
            ],

            'settlement_time' => [
                'nullable',
                'date',
            ],

            'va_numbers' => [
                'nullable',
                'array',
            ],

            'permata_va_number' => [
                'nullable',
                'string',
            ],

            'va_numbers.*.bank' => [
                'nullable',
                'string',
            ],

            'va_numbers.*.va_number' => [
                'nullable',
                'string',
            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'transaction_id.required' =>
                'Transaction ID wajib ada.',

            'transaction_status.required' =>
                'Transaction status wajib ada.',

            'order_id.required' =>
                'Order ID wajib ada.',

            'status_code.required' =>
                'Status code wajib ada.',

            'gross_amount.required' =>
                'Gross amount wajib ada.',

            'signature_key.required' =>
                'Signature key wajib ada.',

        ];
    }

    /**
     * Safe Payload
     */
    public function payload(): array
    {
        return $this->all();
    }
}