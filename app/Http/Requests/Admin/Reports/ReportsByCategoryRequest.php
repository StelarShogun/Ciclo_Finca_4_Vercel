<?php

namespace App\Http\Requests\Admin\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReportsByCategoryRequest extends FormRequest
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
            'date_range' => ['nullable', 'string', Rule::in(['today', 'week', 'month', 'year', 'custom'])],
            'date_from' => ['required_if:date_range,custom', 'nullable', 'date'],
            'date_to' => ['required_if:date_range,custom', 'nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_from.required_if' => 'La fecha de inicio es obligatoria.',
            'date_from.date' => 'La fecha de inicio no es válida.',
            'date_to.required_if' => 'La fecha de fin es obligatoria.',
            'date_to.date' => 'La fecha de fin no es válida.',
            'date_to.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}
