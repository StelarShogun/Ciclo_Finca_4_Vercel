<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'categoria_id' => 'required|exists:categorias,categoria_id',
            'proveedor_id' => 'required|exists:proveedores,proveedor_id',
            'nombre' => 'required|string|max:200|unique:productos,nombre',
            'descripcion' => 'nullable|string',
            'precio_venta' => 'required|numeric|min:0|gt:precio_compra',
            'precio_compra'=> 'required|numeric|min:0',
            'stock_actual'=> 'required|integer|min:0|gte:stock_minimo',
            'stock_minimo'=> 'required|integer|min:0',
            'estado' => 'required|in:activo,inactivo,agotado,descontinuado',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    public function messages(): array {
        return [
            'required' => 'Este campo es obligatorio.',
            'exists' => 'La opción seleccionada no es válida.',
            'string' => 'Debe ser texto.',
            'max' => 'No puede tener más de :max caracteres.',
            'numeric' => 'Debe ser un número.',
            'min' => 'Debe ser como mínimo :min.',
            'integer' => 'Debe ser un número entero.',
            'in' => 'La opción seleccionada no es válida.',
            'unique' => 'Ya existe un producto con este nombre.',
            'gt' => 'Debe ser mayor que el precio de compra.',
            'gte' => 'Debe ser mayor o igual al stock mínimo.',
            'image' => 'Debe ser una imagen válida.',
            'mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif o svg.',
            'max.2048' => 'La imagen no puede pesar más de 2MB.',
        ];
    }

    public function attributes(): array {
        return [
            'categoria_id' => 'categoría',
            'proveedor_id' => 'proveedor',
            'nombre' => 'nombre del producto',
            'descripcion' => 'descripción',
            'precio_venta' => 'precio de venta',
            'precio_compra' => 'precio de compra',
            'stock_actual' => 'cantidad en stock',
            'stock_minimo' => 'stock mínimo',
            'estado' => 'estado',
            'imagen' => 'imagen',
        ];
    }
}
