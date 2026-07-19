<?php

namespace App\Http\Requests\Admin\Series;
use App\Models\Series;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeriesRequest extends FormRequest
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
        $series = $this->route('series');

        return [

            'name' => [

                'required',

                'string',

                'max:100',

                Rule::unique('series', 'name')
                    ->ignore($series),

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
