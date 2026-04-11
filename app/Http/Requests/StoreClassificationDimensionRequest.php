<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassificationDimensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = (int) $this->route('category')->category_id;

        return [
            'slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('classification_dimensions', 'slug')->where(fn ($q) => $q->where('category_id', $categoryId)),
            ],
            'label' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'La clave técnica (slug) es obligatoria.',
            'slug.regex' => 'El slug solo puede usar minúsculas, números, guiones y guión bajo.',
            'slug.unique' => 'Ya existe otro dato con ese código interno para este tipo de producto.',
            'label.required' => 'La etiqueta visible es obligatoria.',
        ];
    }
}
