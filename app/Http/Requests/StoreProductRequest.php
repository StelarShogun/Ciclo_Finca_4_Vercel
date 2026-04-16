<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Services\ProductClassificationAssignmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->input('category_id');

        $nameRule = Rule::unique('products', 'name');
        if (filled($categoryId)) {
            $nameRule->where(fn ($query) => $query->where('category_id', $categoryId));
        }

        return [
            'category_id' => 'required|exists:categories,category_id',
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'brand_id' => 'required|exists:brands,id',
            'name' => ['required', 'string', 'max:200', $nameRule],
            'description' => 'nullable|string',
            'sale_price' => 'required|numeric|min:0|gt:purchase_price',
            'purchase_price' => 'required|numeric|min:0',
            'stock_current' => 'required|integer|min:0',
            'stock_minimum' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive,out_of_stock,discontinued',
            'is_featured' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'classification_value_ids' => 'nullable|array',
            'classification_value_ids.*' => ['integer', Rule::exists('classification_values', 'id')->whereNull('deleted_at')],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('classification_value_ids') || ! is_array($this->input('classification_value_ids'))) {
            $this->merge(['classification_value_ids' => []]);

            return;
        }
        $filtered = array_values(array_filter(
            $this->input('classification_value_ids', []),
            fn ($v) => $v !== null && $v !== '' && $v !== false
        ));
        $this->merge(['classification_value_ids' => array_map('intval', $filtered)]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $ids = $this->input('classification_value_ids', []);
            if (! is_array($ids) || $ids === []) {
                return;
            }
            $categoryId = (int) $this->input('category_id');
            $category = Category::query()->find($categoryId);
            if (! $category || $category->parent_category_id === null) {
                $validator->errors()->add('classification_value_ids', 'Color, talla, etc. solo aplican cuando el producto tiene un tipo concreto (no solo el rubro).');

                return;
            }
            try {
                app(ProductClassificationAssignmentService::class)->assertValuesValidForCategory($categoryId, $ids);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($key, $message);
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El valor seleccionado en :attribute no es válido.',
            'string' => 'El campo :attribute debe ser texto.',
            'max.string' => 'El campo :attribute no puede superar :max caracteres.',
            'max.file' => 'El archivo :attribute no puede superar :max kilobytes.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'min.numeric' => 'El campo :attribute debe ser al menos :min.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'min.integer' => 'El campo :attribute debe ser al menos :min.',
            'in' => 'El valor seleccionado en :attribute no es válido.',
            'unique' => 'Ya existe un producto con este nombre en esta categoría.',
            'array' => 'El campo :attribute debe ser una lista válida.',
            'image' => 'El campo :attribute debe ser una imagen válida.',
            'mimes' => 'El campo :attribute solo admite: jpeg, png, jpg, gif o svg.',

            'sale_price.gt' => 'El precio de venta debe ser mayor que el precio de compra.',
            'stock_minimum.min' => 'El stock mínimo debe ser mayor o igual a 0.',
            'stock_current.min' => 'El stock actual debe ser mayor o igual a 0.',
            'images.*.image' => 'Cada imagen adicional debe ser un archivo de imagen válido.',
            'images.*.mimes' => 'Cada imagen adicional debe ser jpeg, png, jpg, gif o svg.',
            'images.*.max' => 'Cada imagen adicional no puede superar :max kilobytes.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'categoría',
            'supplier_id' => 'proveedor',
            'name' => 'nombre del producto',
            'description' => 'descripción',
            'sale_price' => 'precio de venta',
            'purchase_price' => 'precio de compra',
            'stock_current' => 'stock actual',
            'stock_minimum' => 'stock mínimo',
            'status' => 'estado',
            'is_featured' => 'destacado en tienda',
            'image' => 'imagen del producto',
            'images' => 'imágenes adicionales',
            'images.*' => 'imagen adicional',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }
}
