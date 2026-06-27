<?php

namespace App\Http\Requests\Admin\Sales;

use App\Enums\Sales\SalePaymentMethod;
use App\Support\AdminDateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminSalesIndexRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::in(['completed', 'cancelled', 'returned', 'all'])],
            'date_range' => ['nullable', 'string', Rule::in([
                AdminDateRange::PRESET_TODAY,
                AdminDateRange::PRESET_WEEK,
                AdminDateRange::PRESET_MONTH,
                AdminDateRange::PRESET_CUSTOM,
            ])],
            'date_from' => ['nullable', 'date', 'required_with:date_to'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'payment_method' => ['nullable', 'string', Rule::in(SalePaymentMethod::values())],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->query('search', '')),
            'payment_method' => trim((string) $this->query('payment_method', '')),
        ]);
    }
}
