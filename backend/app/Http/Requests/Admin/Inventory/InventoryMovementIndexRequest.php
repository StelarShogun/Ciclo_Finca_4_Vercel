<?php

namespace App\Http\Requests\Admin\Inventory;

use App\Enums\Inventory\MovementType;
use App\Services\Admin\Inventory\InventoryMovementService;
use App\Support\AdminPerPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InventoryMovementIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null || $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => mb_substr(trim((string) $this->input('search', '')), 0, 100),
            'per_page' => $this->input('per_page', 10),
            'page' => $this->input('page', 1),
        ]);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', Rule::in(array_column(MovementType::cases(), 'value'))],
            'origin' => ['nullable', 'string', Rule::in(InventoryMovementService::VALID_ORIGINS)],
            'date_range' => ['nullable', 'string', Rule::in(['today'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in(AdminPerPage::ALLOWED)],
        ];
    }
}
