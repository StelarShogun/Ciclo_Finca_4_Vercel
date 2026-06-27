<?php

namespace App\Http\Requests\Admin\Reports;

use App\Enums\Reports\ReportExportFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegistryExportRequest extends FormRequest
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
            'format' => ['nullable', 'string', Rule::in(ReportExportFormat::values())],
            'scope' => ['nullable', 'string', Rule::in(['filtered', 'all'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', 'max:50'],
            'supplier_id' => ['nullable', 'integer'],
            'client_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:120'],
        ];
    }
}
