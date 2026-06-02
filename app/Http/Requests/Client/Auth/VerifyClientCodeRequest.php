<?php

namespace App\Http\Requests\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyClientCodeRequest extends FormRequest
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
            'verification_code' => 'required|digits:6',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'verification_code.required' => 'El código es obligatorio.',
            'verification_code.digits' => 'El código debe tener exactamente 6 dígitos.',
        ];
    }
}
