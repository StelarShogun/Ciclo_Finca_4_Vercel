<?php

namespace App\Http\Requests\Admin\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SalesPerformanceViewRequest extends FormRequest
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
            'preset' => ['nullable', 'string', Rule::in(['today', 'week', 'month', 'year', 'custom'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * @return array{initialPreset: string, initialFrom: string, initialTo: string}
     */
    public function initialState(): array
    {
        return [
            'initialPreset' => (string) $this->validated('preset', 'month'),
            'initialFrom' => (string) $this->validated('from', ''),
            'initialTo' => (string) $this->validated('to', ''),
        ];
    }

    protected function prepareForValidation(): void
    {
        $preset = $this->query('preset', 'month');
        $allowed = ['today', 'week', 'month', 'year', 'custom'];
        $preset = is_string($preset) && in_array($preset, $allowed, true) ? $preset : 'month';

        $this->merge([
            'preset' => $preset,
        ]);
    }
}
