<?php

namespace App\Http\Requests\Admin\Products;

use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

final class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('sku')) {
            return;
        }

        $raw = $this->input('sku');
        $normalizedSku = null;

        if (is_string($raw)) {
            $trimmed = trim($raw);
            $normalizedSku = $trimmed === '' ? null : $trimmed;
        }

        $this->merge(['sku' => $normalizedSku]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock_current' => ['required', 'integer', 'min:0'],
        ];

        if ($this->has('sku')) {
            $variant = $this->route('variant');
            $variantId = $variant instanceof Product ? (int) $variant->product_id : 0;

            $rules['sku'] = [
                'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('products', 'sku')->ignore($variantId, 'product_id'),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sale_price.required' => 'El precio es obligatorio.',
            'stock_current.required' => 'El stock es obligatorio.',
            'sku.regex' => 'El SKU solo puede contener letras, números y guiones.',
            'sku.unique' => 'Ese SKU ya está en uso por otro producto.',
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
