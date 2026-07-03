<?php

namespace App\Http\Requests\Client\Cart;

use App\Http\Requests\Concerns\ResolvesPublicProductId;
use Illuminate\Foundation\Http\FormRequest;

final class AddToCartRequest extends FormRequest
{
    use ResolvesPublicProductId;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,product_id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
