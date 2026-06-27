<?php

namespace App\Http\Requests\Admin\Dashboard;

use App\Enums\Reports\ReportExportFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DashboardExportRequest extends FormRequest
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
            'period' => ['nullable', 'string', Rule::in(['7d', '30d', '90d'])],
        ];
    }

    public function exportFormat(): string
    {
        $format = $this->validated('format');

        return is_string($format) ? $format : ReportExportFormat::Pdf->value;
    }

    public function period(): string
    {
        $period = $this->validated('period');

        return is_string($period) ? $period : '7d';
    }
}
