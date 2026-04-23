<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SalesPerformanceRangeRequest extends FormRequest
{
    /** Máximo de días inclusivos permitidos en rango personalizado (debe coincidir con el frontend). */
    public const MAX_CUSTOM_RANGE_DAYS_INCLUSIVE = 731;

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
            'to.after_or_equal' => 'Revisá el rango: la fecha final debe ser desde el 1 de enero de 2025 y no puede ser menor que la inicial.',
            'from.after_or_equal' => 'Las fechas deben ser desde el 1 de enero de 2025 en adelante (política del reporte).',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('preset') !== 'custom') {
                return;
            }

            $from = $this->input('from');
            $to = $this->input('to');
            if (! is_string($from) || ! is_string($to) || $validator->errors()->has('from') || $validator->errors()->has('to')) {
                return;
            }

            try {
                $start = Carbon::parse($from)->startOfDay();
                $end = Carbon::parse($to)->startOfDay();
            } catch (\Throwable) {
                return;
            }

            if ($end < $start) {
                return;
            }

            $daysInclusive = (int) $start->diffInDays($end) + 1;
            if ($daysInclusive > self::MAX_CUSTOM_RANGE_DAYS_INCLUSIVE) {
                $validator->errors()->add(
                    'from',
                    'El rango personalizado no puede superar '.self::MAX_CUSTOM_RANGE_DAYS_INCLUSIVE.' días (incluyendo inicio y fin). Acortá el periodo o usá filtros predefinidos (mes, año).',
                );
            }
        });
    }
}
