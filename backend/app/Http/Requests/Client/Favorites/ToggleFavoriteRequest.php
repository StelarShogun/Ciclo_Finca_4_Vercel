<?php

namespace App\Http\Requests\Client\Favorites;

use Illuminate\Foundation\Http\FormRequest;

final class ToggleFavoriteRequest extends FormRequest
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
            'product_id' => ['required', 'integer', 'exists:products,product_id'],
        ];
    }
}
