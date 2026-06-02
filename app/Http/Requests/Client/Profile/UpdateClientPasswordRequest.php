<?php

namespace App\Http\Requests\Client\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateClientPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('clients')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $client = Auth::guard('clients')->user();
        $isGoogle = ($client->provider ?? 'local') === 'google';

        $rules = [
            'new_password' => 'required|string|min:8|confirmed',
        ];

        if (! $isGoogle) {
            $rules['current_password'] = 'required|string';
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'current_password.string' => 'La contraseña actual no es válida.',
            'new_password.required' => 'La nueva contraseña es obligatoria.',
            'new_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    public function isGoogleOnlyAccount(): bool
    {
        $client = Auth::guard('clients')->user();

        return ($client->provider ?? 'local') === 'google';
    }
}
