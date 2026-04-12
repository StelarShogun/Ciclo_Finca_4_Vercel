<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'stock_current' => 'required|integer|min:0|gte:stock_minimum',
            'stock_minimum' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive,out_of_stock,discontinued',
            'is_featured' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
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
            'stock_current.gte' => 'El stock actual debe ser mayor o igual al stock mínimo.',

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
