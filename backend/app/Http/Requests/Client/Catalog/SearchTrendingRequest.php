<?php

namespace App\Http\Requests\Client\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SearchTrendingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $limit = (int) $this->query('limit', 8);

        $this->merge([
            'period' => (string) $this->query('period', '30d'),
            'limit' => max(1, min(10, $limit ?: 8)),
        ]);
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'string', Rule::in(['7d', '30d', '90d'])],
            'limit' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }
}
