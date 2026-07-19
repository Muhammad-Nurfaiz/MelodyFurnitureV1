<?php

namespace App\Http\Requests\Admin\Series;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSeriesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'name' => [
                'required',
                'string',
                'max:100',
                'unique:series,name',
            ],

            'description' => [
                'nullable',
                'string',
            ],

        ];
    }

    public function attributes(): array
    {
        return [

            'name' => 'nama series',

            'description' => 'deskripsi',

        ];
    }
}
