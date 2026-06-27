<?php

namespace App\Http\Requests\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetClientPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'new_password.required' => 'La nueva contraseña es obligatoria.',
            'new_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed' => 'Las contraseñas no coinciden.',
            'new_password_confirmation.required' => 'Debes confirmar la nueva contraseña.',
        ];
    }
}
