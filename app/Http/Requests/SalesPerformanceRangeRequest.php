<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalesPerformanceRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $today = now()->format('Y-m-d');

        return [
            'preset' => ['required', 'string', 'in:today,week,month,year,custom'],
            'from' => [
                'required_if:preset,custom',
                'date_format:Y-m-d',
                'after_or_equal:2025-01-01',
                'before_or_equal:'.$today,
                'before_or_equal:to',
            ],
            'to' => [
                'required_if:preset,custom',
                'date_format:Y-m-d',
                'after_or_equal:2025-01-01',
                'before_or_equal:'.$today,
                'after_or_equal:from',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'preset.required' => 'Debes seleccionar un rango de fechas.',
            'preset.in' => 'El rango seleccionado no es válido.',
            'from.required_if' => 'La fecha inicial es obligatoria para rango personalizado.',
            'to.required_if' => 'La fecha final es obligatoria para rango personalizado.',
            'from.date_format' => 'La fecha inicial no es válida.',
            'to.date_format' => 'La fecha final no es válida.',
            'from.before_or_equal' => 'Revisá el rango: la fecha inicial no puede ser mayor que la final ni posterior a hoy.',
            'to.after_or_equal' => 'Revisá el rango: la fecha final no puede ser menor que la inicial.',
            'from.after_or_equal' => 'La fecha debe ser desde el 1 de enero de 2025.',
            'to.after_or_equal' => 'La fecha debe ser desde el 1 de enero de 2025.',
        ];
    }
}
