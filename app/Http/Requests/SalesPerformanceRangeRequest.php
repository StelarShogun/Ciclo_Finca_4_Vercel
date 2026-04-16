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
        return [
            'preset' => ['required', 'string', 'in:today,week,month,year,custom'],
            'from' => ['required_if:preset,custom', 'date_format:Y-m-d', 'before_or_equal:to'],
            'to' => ['required_if:preset,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function messages(): array
    {
        return [
            'preset.required' => 'Debes seleccionar un rango de fechas.',
            'preset.in' => 'El rango seleccionado no es válido.',
            'from.required_if' => 'La fecha inicial es obligatoria para rango personalizado.',
            'to.required_if' => 'La fecha final es obligatoria para rango personalizado.',
            'from.date_format' => 'La fecha inicial debe tener formato AAAA-MM-DD.',
            'to.date_format' => 'La fecha final debe tener formato AAAA-MM-DD.',
            'from.before_or_equal' => 'La fecha inicial no puede ser mayor que la fecha final.',
            'to.after_or_equal' => 'La fecha final no puede ser menor que la fecha inicial.',
        ];
    }
}
