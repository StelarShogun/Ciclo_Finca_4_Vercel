<?php

namespace App\Http\Requests\Admin\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DashboardIndexRequest extends FormRequest
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
            // date_format:Y-m-d rechaza cadenas relativas ("tomorrow", "next year")
            // que la regla 'date' aceptaría vía strtotime; el front sólo envía ISO.
            'range' => ['nullable', 'string', Rule::in(['last7', 'last15', 'last30', 'month', 'custom'])],
            'from' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:2020-01-01'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }
}
