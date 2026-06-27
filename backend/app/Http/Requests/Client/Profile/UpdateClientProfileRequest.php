<?php

namespace App\Http\Requests\Client\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateClientProfileRequest extends FormRequest
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

        return [
            'name' => 'required|string|min:2|max:60',
            'first_surname' => 'required|string|min:2|max:60',
            'second_surname' => 'nullable|string|max:60',
            'gmail' => 'required|email|max:100|unique:client_table,gmail,'.$client->user_id.',user_id',
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
            'name.max' => 'El nombre no puede superar los 60 caracteres.',
            'first_surname.required' => 'El primer apellido es obligatorio.',
            'first_surname.min' => 'El primer apellido debe tener al menos 2 caracteres.',
            'first_surname.max' => 'El primer apellido no puede superar los 60 caracteres.',
            'second_surname.max' => 'El segundo apellido no puede superar los 60 caracteres.',
            'gmail.required' => 'El correo electrónico es obligatorio.',
            'gmail.email' => 'El formato del correo electrónico no es válido.',
            'gmail.max' => 'El correo electrónico no puede superar los 100 caracteres.',
            'gmail.unique' => 'Este correo electrónico ya está registrado.',
        ];
    }
}
