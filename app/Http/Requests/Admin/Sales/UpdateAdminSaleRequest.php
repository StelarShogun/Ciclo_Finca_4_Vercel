<?php

namespace App\Http\Requests\Admin\Sales;

use App\Enums\Sales\SaleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAdminSaleRequest extends FormRequest
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
            'status' => ['required', Rule::in(SaleStatus::values())],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
