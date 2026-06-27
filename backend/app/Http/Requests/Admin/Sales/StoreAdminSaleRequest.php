<?php

namespace App\Http\Requests\Admin\Sales;

use App\Enums\Sales\SalePaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAdminSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items', $this->input('productos', []));

        $normalizedItems = collect(is_array($items) ? $items : [])->map(function (array $item): array {
            $item['product_id'] = $item['product_id'] ?? $item['producto_id'] ?? null;
            $item['quantity'] = $item['quantity'] ?? $item['cantidad'] ?? 1;

            return $item;
        })->all();

        $this->merge([
            'items' => $normalizedItems,
            'payment_method' => $this->input('payment_method', $this->mapPaymentMethodToEnglish($this->input('metodo_pago'))),
            'payment_reference' => $this->input('payment_reference', $this->input('referencia_pago')),
            'discount' => $this->input('discount', $this->input('descuento')),
            'notes' => $this->input('notes', $this->input('notas')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buyer_name' => ['nullable', 'string', 'max:120'],
            'buyer_email' => ['nullable', 'email', 'max:150'],
            'client_id' => ['nullable', 'exists:client_table,user_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,product_id'],
            'items.*.producto_id' => ['nullable'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.cantidad' => ['nullable', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'items.*.total' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in([
                SalePaymentMethod::Cash->value,
                SalePaymentMethod::Sinpe->value,
                SalePaymentMethod::Transfer->value,
            ])],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'iva_percentage' => ['nullable', 'numeric', 'min:0', 'max:13'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
        ];
    }

    private function mapPaymentMethodToEnglish(mixed $value): mixed
    {
        if (empty($value)) {
            return $value;
        }

        $map = ['efectivo' => 'cash', 'sinpe' => 'sinpe', 'transferencia' => 'transfer'];

        return $map[strtolower((string) $value)] ?? $value;
    }
}
