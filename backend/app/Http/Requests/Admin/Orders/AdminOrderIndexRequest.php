<?php

namespace App\Http\Requests\Admin\Orders;

use App\Support\AdminPerPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminOrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => (string) $this->query('status', ''),
            'search' => mb_substr(trim((string) $this->query('search', '')), 0, 100),
            'per_page' => $this->query('per_page', 10),
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(['pending', 'ready_to_pickup', 'completed', 'cancelled', 'refunded'])],
            'search' => ['nullable', 'string', 'max:100'],
            'date_range' => ['nullable', 'string', Rule::in(['today', 'week', 'month', 'year', 'custom'])],
            'date_from' => ['required_if:date_range,custom', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['required_if:date_range,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', Rule::in(AdminPerPage::ALLOWED)],
        ];
    }
}
