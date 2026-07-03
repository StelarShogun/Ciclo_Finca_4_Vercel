<?php

namespace App\Http\Requests\Client\Auth;

use App\Rules\Recaptcha;
use Illuminate\Foundation\Http\FormRequest;

class LoginClientRequest extends FormRequest
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
        $rules = [
            'gmail' => 'required|email',
            'password' => 'required',
        ];

        if (config('recaptcha.site_key')) {
            $rules['g-recaptcha-response'] = ['required', new Recaptcha];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gmail.required' => 'El correo electrónico es obligatorio.',
            'gmail.email' => 'El formato del correo electrónico no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }
}
