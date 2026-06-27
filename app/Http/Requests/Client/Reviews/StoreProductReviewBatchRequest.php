<?php

namespace App\Http\Requests\Client\Reviews;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProductReviewBatchRequest extends FormRequest
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
            'reviews' => ['required', 'array', 'min:1'],
            'reviews.*.product_id' => ['required', 'integer', 'exists:products,product_id'],
            'reviews.*.stars' => ['required', 'integer', 'between:1,5'],
        ];
    }
}
