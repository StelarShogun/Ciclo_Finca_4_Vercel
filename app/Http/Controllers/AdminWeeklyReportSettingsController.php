<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminWeeklyReportSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'weekly_report_day' => ['required', 'integer', Rule::in([0, 1, 2, 3, 4, 5, 6])],
            'weekly_report_hour' => ['required', 'integer', 'min:0', 'max:23'],
            'weekly_report_minute' => ['required', 'integer', 'min:0', 'max:59'],
            'weekly_report_recipients' => ['required', 'string'],
        ], [
            'weekly_report_day.required' => 'Seleccione el día de envío.',
            'weekly_report_day.in' => 'El día debe ser un valor entre 0 (domingo) y 6 (sábado).',
            'weekly_report_hour.required' => 'Indique la hora de envío.',
            'weekly_report_hour.min' => 'La hora debe estar entre 0 y 23.',
            'weekly_report_hour.max' => 'La hora debe estar entre 0 y 23.',
            'weekly_report_minute.required' => 'Indique los minutos de envío.',
            'weekly_report_minute.min' => 'Los minutos deben estar entre 0 y 59.',
            'weekly_report_minute.max' => 'Los minutos deben estar entre 0 y 59.',
            'weekly_report_recipients.required' => 'Ingrese al menos un correo destinatario.',
        ]);

        // Parse recipients: comma/newline/semicolon separated, one per line, trimmed and validated.
        $rawRecipients = preg_split('/[\s,;]+/', $validated['weekly_report_recipients']);
        $emails = collect($rawRecipients)
            ->map(fn (string $e) => trim($e))
            ->filter(fn (string $e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        if (empty($emails)) {
            $error = 'Ingrese al menos un correo electrónico válido.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $error, 'errors' => ['weekly_report_recipients' => [$error]]], 422);
            }

            return back()->withErrors(['weekly_report_recipients' => $error])->withInput();
        }

        AppSetting::setWeeklyReportDay((int) $validated['weekly_report_day']);
        AppSetting::setWeeklyReportHour((int) $validated['weekly_report_hour']);
        AppSetting::setWeeklyReportMinute((int) $validated['weekly_report_minute']);
        AppSetting::setWeeklyReportRecipients($emails);

        Log::info('admin.weekly_report_settings_update', [
            'day' => $validated['weekly_report_day'],
            'hour' => $validated['weekly_report_hour'],
            'minute' => $validated['weekly_report_minute'],
            'recipients' => $emails,
        ]);

        $message = 'Configuración del reporte semanal actualizada correctamente.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'weekly_report_day' => (int) $validated['weekly_report_day'],
                'weekly_report_hour' => (int) $validated['weekly_report_hour'],
                'weekly_report_minute' => (int) $validated['weekly_report_minute'],
                'weekly_report_recipients' => $emails,
            ]);
        }

        return redirect()
            ->route('admin.orders.index')
            ->with('status', $message);
    }
}
