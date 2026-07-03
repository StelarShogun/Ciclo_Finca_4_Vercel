<?php

namespace App\Http\Requests\Admin\Clients;

use App\Services\Admin\ClientPurchaseHistoryQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ClientPurchaseHistoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'period' => ClientPurchaseHistoryQuery::sanitizePeriod((string) $this->input('period', '30d')),
            'sort' => ClientPurchaseHistoryQuery::sanitizeSort((string) $this->input('sort', 'total_purchased')),
            'dir' => ClientPurchaseHistoryQuery::sanitizeDir((string) $this->input('dir', 'desc')),
            'q' => ClientPurchaseHistoryQuery::normalizeSearchInput(is_string($this->input('q')) ? $this->input('q') : null),
        ]);
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'string', Rule::in(ClientPurchaseHistoryQuery::PERIODS)],
            'sort' => ['required', 'string', Rule::in(ClientPurchaseHistoryQuery::SORTS)],
            'dir' => ['required', 'string', Rule::in(['asc', 'desc'])],
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }
}
