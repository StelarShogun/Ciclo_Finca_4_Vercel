<?php

namespace App\Http\Requests\Admin\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

final class AnalyzeXmlPriceDeviationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'xml_file' => ['required', 'file', 'mimes:xml,text', 'max:5120'],
            'threshold' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'xml_file.required' => 'Debe seleccionar un archivo XML.',
            'xml_file.mimes' => 'El archivo debe ser de tipo XML.',
            'xml_file.max' => 'El archivo no puede superar los 5 MB.',
            'threshold.required' => 'Debe indicar el umbral de desvío.',
            'threshold.min' => 'El umbral no puede ser negativo.',
            'threshold.max' => 'El umbral no puede superar el 100%.',
        ];
    }
}
