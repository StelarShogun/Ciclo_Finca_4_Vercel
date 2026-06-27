<?php

namespace App\Http\Requests\Client\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateClientAvatarRequest extends FormRequest
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
        return [
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.required' => 'Seleccioná una imagen.',
            'avatar.image' => 'El archivo debe ser una imagen.',
            'avatar.mimes' => 'Formatos permitidos: JPG, PNG o WebP.',
            'avatar.max' => 'La imagen no puede superar los 2 MB.',
        ];
    }
}
