<?php

namespace App\Http\Requests\Admin\Sales;

use Illuminate\Foundation\Http\FormRequest;

final class ReturnAdminSaleRequest extends FormRequest
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
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Debe ingresar un motivo de devolución.',
            'reason.min' => 'El motivo debe tener al menos 3 caracteres.',
            'reason.max' => 'El motivo no puede superar los 500 caracteres.',
        ];
    }
}
