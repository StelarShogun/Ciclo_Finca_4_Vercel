<?php

namespace App\Http\Requests\Admin\Clients;

use App\Services\Admin\ClientPurchaseHistoryQuery;
use App\Support\AdminPerPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientPurchaseHistoryTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('q');
        $q = ClientPurchaseHistoryQuery::normalizeSearchInput(is_string($raw) ? $raw : null);
        $this->merge([
            'page' => $this->input('page', 1),
            'per_page' => $this->input('per_page', 10),
            'q' => $q,
        ]);
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'string', Rule::in(ClientPurchaseHistoryQuery::PERIODS)],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['required', 'string', Rule::in(ClientPurchaseHistoryQuery::SORTS)],
            'dir' => ['required', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in(AdminPerPage::ALLOWED)],
        ];
    }
}
