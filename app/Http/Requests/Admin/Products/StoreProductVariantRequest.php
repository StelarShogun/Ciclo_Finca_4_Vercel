<?php

namespace App\Http\Requests\Admin\Products;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class StoreProductVariantRequest extends FormRequest
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
            'variant_product_id' => ['required', 'integer', 'exists:products,product_id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'variant_product_id.required' => 'Selecciona una variante.',
            'variant_product_id.exists' => 'La variante seleccionada no existe.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Revisa los datos enviados.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
