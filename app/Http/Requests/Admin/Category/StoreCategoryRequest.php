<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'name' => [

                'required',

                'string',

                'max:50',

                'unique:categories,name'

            ],

        ];
    }

    public function attributes(): array
    {
        return [

            'name' => 'nama kategori',

            'status' => 'status',

        ];
    }

    public function messages(): array
    {
        return [

            'name.required' => 'Nama kategori wajib diisi.',

            'name.unique' => 'Nama kategori sudah digunakan.',

            'status.required' => 'Status wajib dipilih.',

        ];
    }
}