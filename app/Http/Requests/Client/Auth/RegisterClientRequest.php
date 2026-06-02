<?php

namespace App\Http\Requests\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterClientRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50', 'min:2', 'regex:/^[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+$/u'],
            'first_surname' => ['required', 'string', 'max:50', 'min:2', 'regex:/^[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+$/u'],
            'second_surname' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+$/u'],
            'gmail' => ['required', 'email', 'unique:client_table,gmail', 'regex:/^[^@]+@gmail\.com$/i'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'accept_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede superar 50 caracteres.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'first_surname.required' => 'El apellido es obligatorio.',
            'first_surname.min' => 'El apellido debe tener al menos 2 caracteres.',
            'first_surname.max' => 'El apellido no puede superar 50 caracteres.',
            'first_surname.regex' => 'El apellido solo puede contener letras y espacios.',
            'second_surname.max' => 'El segundo apellido no puede superar 50 caracteres.',
            'second_surname.regex' => 'El segundo apellido solo puede contener letras y espacios.',
            'gmail.required' => 'El correo Gmail es obligatorio.',
            'gmail.email' => 'Debe ingresar un correo electrónico válido.',
            'gmail.unique' => 'Este correo ya está registrado.',
            'gmail.regex' => 'Solo se aceptan correos de Gmail (@gmail.com).',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'accept_terms.accepted' => 'Debes aceptar los Términos y condiciones y la Política de privacidad.',
        ];
    }
}
