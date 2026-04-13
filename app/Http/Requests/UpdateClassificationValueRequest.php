<?php

namespace App\Http\Requests;

use App\Models\ClassificationValue;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClassificationValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            /** @var ClassificationValue $valueModel */
            $valueModel = $this->route('value');
            $dimId = (int) $valueModel->classification_dimension_id;
            $norm = ClassificationValue::normalizeStoredValue((string) $this->input('value'));

            $dup = ClassificationValue::withTrashed()
                ->where('classification_dimension_id', $dimId)
                ->where('normalized_value', $norm)
                ->where('id', '!=', $valueModel->id)
                ->exists();

            if ($dup) {
                $validator->errors()->add('value', 'Esa opción ya existe (el sistema la reconoce como igual).');
            }
        });
    }

    public function messages(): array
    {
        return [
            'value.required' => 'El valor es obligatorio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('value') && is_string($this->input('value'))) {
            $this->merge(['value' => trim($this->input('value'))]);
        }
    }
}
