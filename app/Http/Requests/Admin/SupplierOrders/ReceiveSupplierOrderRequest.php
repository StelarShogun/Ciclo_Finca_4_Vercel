<?php

namespace App\Http\Requests\Admin\SupplierOrders;

use Illuminate\Foundation\Http\FormRequest;

final class ReceiveSupplierOrderRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.received_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Debes enviar las líneas del pedido.',
            'items.*.order_item_id.required' => 'Cada línea debe tener un identificador.',
            'items.*.received_quantity.required' => 'La cantidad recibida es obligatoria.',
            'items.*.received_quantity.min' => 'La cantidad recibida no puede ser negativa.',
        ];
    }
}
