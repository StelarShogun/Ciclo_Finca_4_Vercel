<?php

namespace App\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateWeeklyReportSettingsRequest extends FormRequest
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
            'weekly_report_day' => ['required', 'integer', Rule::in([0, 1, 2, 3, 4, 5, 6])],
            'weekly_report_hour' => ['required', 'integer', 'min:0', 'max:23'],
            'weekly_report_minute' => ['required', 'integer', 'min:0', 'max:59'],
            'weekly_report_recipients' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'weekly_report_day.required' => 'Seleccione el día de envío.',
            'weekly_report_day.in' => 'El día debe ser un valor entre 0 (domingo) y 6 (sábado).',
            'weekly_report_hour.required' => 'Indique la hora de envío.',
            'weekly_report_hour.min' => 'La hora debe estar entre 0 y 23.',
            'weekly_report_hour.max' => 'La hora debe estar entre 0 y 23.',
            'weekly_report_minute.required' => 'Indique los minutos de envío.',
            'weekly_report_minute.min' => 'Los minutos deben estar entre 0 y 59.',
            'weekly_report_minute.max' => 'Los minutos deben estar entre 0 y 59.',
            'weekly_report_recipients.required' => 'Ingrese al menos un correo destinatario.',
        ];
    }
}
