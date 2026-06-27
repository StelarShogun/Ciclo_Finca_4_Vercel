<?php

namespace App\Http\Requests\Admin\SupplierOrders;

use Illuminate\Foundation\Http\FormRequest;

final class ClosePartialSupplierOrderRequest extends FormRequest
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
            'reason' => ['required', 'string', 'min:4', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Debes indicar un motivo para cerrar el pedido con faltantes.',
            'reason.min' => 'El motivo debe tener al menos 4 caracteres.',
            'reason.max' => 'El motivo no puede superar los 500 caracteres.',
        ];
    }
}
