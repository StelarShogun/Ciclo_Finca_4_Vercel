<?php

namespace App\Http\Requests\Admin\Products;

use App\Models\Product;
use App\Services\Admin\Classifications\ProductClassificationAssignmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateProductClassificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classification_value_ids' => ['nullable', 'array'],
            'classification_value_ids.*' => ['nullable', 'integer', Rule::exists('classification_values', 'id')->whereNull('deleted_at')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('classification_value_ids', []);
        if (! is_array($raw)) {
            $this->merge(['classification_value_ids' => []]);

            return;
        }
        $filtered = array_values(array_filter($raw, fn ($v) => $v !== null && $v !== '' && $v !== false));
        $this->merge(['classification_value_ids' => array_map('intval', $filtered)]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            /** @var Product|null $product */
            $product = $this->route('product');
            if (! $product instanceof Product) {
                return;
            }
            $product->loadMissing('category');
            $category = $product->category;
            if (! $category || $category->parent_category_id === null) {
                $validator->errors()->add('classification_value_ids', 'El producto debe estar en un tipo concreto del catálogo (no solo en la categoría padre).');

                return;
            }
            $ids = $this->input('classification_value_ids', []);
            if (! is_array($ids) || $ids === []) {
                return;
            }
            try {
                app(ProductClassificationAssignmentService::class)->assertValuesValidForCategory((int) $product->category_id, $ids);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($key, $message);
                    }
                }
            }
        });
    }
}
