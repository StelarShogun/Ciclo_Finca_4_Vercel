<?php

namespace App\Http\Requests\Admin\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

final class ApplyXmlPriceDeviationRequest extends FormRequest
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
            'updates' => ['present', 'array'],
            'updates.*' => ['integer', 'min:1'],
            'sale_prices' => ['nullable', 'array'],
            'sale_prices.*' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
