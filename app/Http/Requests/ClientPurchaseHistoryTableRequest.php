<?php

namespace App\Http\Requests;

use App\Services\Admin\ClientPurchaseHistoryQuery;
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
        $this->merge([
            'page' => $this->input('page', 1),
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
        ];
    }
}
