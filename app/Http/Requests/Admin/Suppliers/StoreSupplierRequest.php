<?php

namespace App\Http\Requests\Admin\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'min:2'],
            'primary_contact' => ['required', 'string', 'max:100', 'min:2', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\'\s]+$/'],
            'phone' => ['required', 'string', 'min:8', 'max:20'],
            'email' => ['required', 'email', 'max:100', 'min:10', 'unique:suppliers,email'],
            'address' => ['required', 'string', 'min:5', 'max:255'],
            'delivery_time' => ['required', 'integer', 'min:1', 'max:365'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return SupplierRequestMessages::messages();
    }
}
