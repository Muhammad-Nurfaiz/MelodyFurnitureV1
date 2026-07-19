<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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

                'max:100',

                Rule::unique('categories', 'name')

                    ->ignore($this->route('category')),

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

        ];
    }
}