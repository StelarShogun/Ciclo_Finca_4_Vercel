<?php

namespace App\Http\Requests\Client\Catalog;

use Illuminate\Foundation\Http\FormRequest;

final class ProductSuggestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => mb_substr(trim((string) $this->query('search', '')), 0, 80),
        ]);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:80'],
        ];
    }
}
