<?php

namespace App\Http\Requests\Admin\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DashboardChartRequest extends FormRequest
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
            'period' => ['nullable', 'string', Rule::in(['7d', '30d', '90d'])],
        ];
    }

    public function period(): string
    {
        $period = $this->validated('period');

        return is_string($period) ? $period : '7d';
    }
}
