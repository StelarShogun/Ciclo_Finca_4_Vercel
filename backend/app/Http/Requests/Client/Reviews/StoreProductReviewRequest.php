<?php

namespace App\Http\Requests\Client\Reviews;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProductReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('clients')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stars' => ['required', 'integer', 'between:1,5'],
        ];
    }
}
