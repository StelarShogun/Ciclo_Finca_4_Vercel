<?php

namespace App\Http\Requests\Admin\SupplierOrders;

use App\Enums\Suppliers\SupplierOrderStatus;
use App\Support\AdminDateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SupplierOrderIndexRequest extends FormRequest
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
            'state' => ['nullable', 'string', Rule::in(array_merge(['open'], SupplierOrderStatus::values()))],
            'date_range' => ['nullable', 'string', Rule::in([
                AdminDateRange::PRESET_TODAY,
                AdminDateRange::PRESET_WEEK,
                AdminDateRange::PRESET_MONTH,
                AdminDateRange::PRESET_CUSTOM,
            ])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->query('search', '')),
        ]);
    }
}
