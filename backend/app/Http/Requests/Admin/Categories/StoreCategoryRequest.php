<?php

namespace App\Http\Requests\Admin\Categories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(
                    fn ($query) => $query->where('parent_category_id', $this->input('parent_category_id'))
                ),
            ],
            'description' => ['nullable', 'string'],
            'parent_category_id' => ['required', 'integer', 'exists:categories,category_id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_category_id.required' => 'Debe seleccionar una categoría padre.',
            'parent_category_id.exists' => 'La categoría padre seleccionada no existe.',
            'name.required' => 'El nombre de la subcategoría es obligatorio.',
            'name.unique' => 'Ya existe una subcategoría con ese nombre bajo la categoría padre seleccionada.',
        ];
    }
}
