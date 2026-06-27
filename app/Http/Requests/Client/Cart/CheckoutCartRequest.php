<?php

namespace App\Http\Requests\Client\Cart;

use App\Enums\Sales\SalePaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CheckoutCartRequest extends FormRequest
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
            'payment_method' => ['required', Rule::in([
                SalePaymentMethod::Cash->value,
                SalePaymentMethod::Sinpe->value,
                SalePaymentMethod::Transfer->value,
            ])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'Seleccione un método de pago.',
            'payment_method.in' => 'Método de pago no válido.',
        ];
    }
}
