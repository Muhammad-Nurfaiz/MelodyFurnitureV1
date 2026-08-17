<?php

namespace App\Http\Requests\Admin\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ExportOrderRequest extends FormRequest
{
    /**
     * Authorize request.
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

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'status' => [
                'nullable',
                'string',
                'in:all,pending,paid,processing,picked_up,shipped,completed,cancelled,req_cancel',
            ],

            'payment_status' => [
                'nullable',
                'string',
                'in:all,unpaid,pending,paid,expired,failed',
            ],

        ];
    }

    /**
     * Validation messages.
     */
    public function messages(): array
    {
        return [

            'start_date.required' =>
                'Tanggal mulai wajib dipilih.',

            'start_date.date' =>
                'Tanggal mulai tidak valid.',

            'end_date.required' =>
                'Tanggal akhir wajib dipilih.',

            'end_date.date' =>
                'Tanggal akhir tidak valid.',

            'end_date.after_or_equal' =>
                'Tanggal akhir harus sama atau setelah tanggal mulai.',

            'status.in' =>
                'Status order tidak valid.',

            'payment_status.in' =>
                'Status pembayaran tidak valid.',

        ];
    }
}