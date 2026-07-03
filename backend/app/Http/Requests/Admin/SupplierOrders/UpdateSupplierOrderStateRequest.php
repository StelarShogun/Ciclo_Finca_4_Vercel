<?php

namespace App\Http\Requests\Admin\SupplierOrders;

use App\Enums\Suppliers\SupplierOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSupplierOrderStateRequest extends FormRequest
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
            'state' => ['required', 'string', Rule::in([
                SupplierOrderStatus::Draft->value,
                SupplierOrderStatus::Pending->value,
                SupplierOrderStatus::Confirmed->value,
                SupplierOrderStatus::Delivered->value,
                SupplierOrderStatus::Cancelled->value,
                SupplierOrderStatus::ClosePartial->value,
            ])],
            'reason' => ['required_if:state,close_partial', 'nullable', 'string', 'min:4', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'state.required' => 'Debes indicar el nuevo estado.',
            'state.in' => 'El estado solicitado no es válido.',
            'reason.required_if' => 'Debes indicar un motivo para cerrar el pedido con faltantes.',
            'reason.min' => 'El motivo debe tener al menos 4 caracteres.',
            'reason.max' => 'El motivo no puede superar los 500 caracteres.',
        ];
    }
}
