<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\UpdateWeeklyReportSettingsRequest;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class WeeklyReportSettingsController extends Controller
{
    public function update(UpdateWeeklyReportSettingsRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

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
            'recipients_count' => count($emails),
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
