<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CancellationRequest extends FormRequest
{
    /**
     * Authorization
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

            'reason' => [

                'required',

                'string',

                'max:255',

            ],

            'note' => [

                'nullable',

                'string',

                'max:2000',

            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'reason.required' =>
                'Alasan pembatalan wajib diisi.',

            'reason.max' =>
                'Alasan pembatalan maksimal 255 karakter.',

            'note.max' =>
                'Catatan maksimal 2000 karakter.',

        ];
    }

    /**
     * Attribute Names
     */
    public function attributes(): array
    {
        return [

            'reason' => 'alasan',

            'note' => 'catatan',

        ];
    }
}