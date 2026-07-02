<?php

namespace App\Http\Requests\Client\Favorites;

use App\Http\Requests\Concerns\ResolvesPublicProductId;
use Illuminate\Foundation\Http\FormRequest;

final class ToggleFavoriteRequest extends FormRequest
{
    use ResolvesPublicProductId;

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
