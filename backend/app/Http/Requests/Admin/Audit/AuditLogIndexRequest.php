<?php

namespace App\Http\Requests\Admin\Audit;

use App\Support\AdminPerPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AuditLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user' => $this->normalizeText($this->input('user')),
            'action_type' => $this->normalizeText($this->input('action_type')),
            'module' => $this->normalizeText($this->input('module')),
            'dir' => strtolower((string) $this->input('dir', 'desc')),
            'per_page' => $this->input('per_page', 10),
            'page' => $this->input('page', 1),
        ]);
    }

    public function rules(): array
    {
        return [
            'user' => ['nullable', 'string', 'max:100'],
            'action_type' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'dir' => ['required', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in(AdminPerPage::ALLOWED)],
        ];
    }

    private function normalizeText(mixed $value): string
    {
        return is_string($value) ? mb_substr(trim($value), 0, 100) : '';
    }
}
