<?php

namespace App\Http\Requests\Admin\Classifications;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassificationDimensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'El nombre del atributo es obligatorio.',
        ];
    }
}
