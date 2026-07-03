<?php

namespace App\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOrderSettingsRequest extends FormRequest
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
            'ready_to_pickup_expiration_hours' => ['required', 'integer', 'min:1', 'max:8760'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ready_to_pickup_expiration_hours.required' => 'Indique el número de horas.',
            'ready_to_pickup_expiration_hours.integer' => 'El plazo debe ser un número entero.',
            'ready_to_pickup_expiration_hours.min' => 'El plazo debe ser mayor que cero.',
            'ready_to_pickup_expiration_hours.max' => 'El plazo no puede superar 8760 horas (1 año).',
        ];
    }
}
