<?php

namespace App\Http\Requests\Admin\SupplierOrders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SearchSupplierProductsRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:120'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,supplier_id'],
            'context' => ['nullable', 'string', Rule::in(['supplier_order', 'sale'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => trim((string) $this->query('q', '')),
        ]);
    }
}
