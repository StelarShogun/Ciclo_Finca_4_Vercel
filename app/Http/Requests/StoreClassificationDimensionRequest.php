<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreClassificationDimensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge([
                'slug' => Str::slug((string) $this->input('slug')),
            ]);
        }
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = (int) (is_object($category) ? ($category->category_id ?? 0) : $category);

        return [
            'label' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('classification_dimensions', 'slug')
                    ->where(fn ($q) => $q->where('category_id', $categoryId)->whereNull('deleted_at')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'El nombre del atributo es obligatorio.',
            'slug.unique' => 'Ya existe un atributo con ese identificador en esta subcategoría.',
        ];
    }
}
