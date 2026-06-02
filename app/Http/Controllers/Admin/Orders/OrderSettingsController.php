<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'ready_to_pickup_expiration_hours' => ['required', 'integer', 'min:1', 'max:8760'],
        ], [
            'ready_to_pickup_expiration_hours.required' => 'Indique el número de horas.',
            'ready_to_pickup_expiration_hours.integer' => 'El plazo debe ser un número entero.',
            'ready_to_pickup_expiration_hours.min' => 'El plazo debe ser mayor que cero.',
            'ready_to_pickup_expiration_hours.max' => 'El plazo no puede superar 8760 horas (1 año).',
        ]);

        $hours = (int) $validated['ready_to_pickup_expiration_hours'];
        $previousHours = AppSetting::getStoredReadyToPickupExpirationHours();
        $previousDays = AppSetting::getStoredReadyToPickupExpirationDays();
        AppSetting::setReadyToPickupExpirationHours($hours);
        $this->logAuditAction(
            'client_order_pickup_settings_update',
            'Configuración de cancelación automática para pedidos listos para recoger actualizada (horas).',
            [
                'from_hours_stored' => $previousHours,
                'from_days_stored_legacy' => $previousDays,
                'to_hours' => $hours,
            ]
        );

        $message = 'Plazo de cancelación automática actualizado correctamente.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'ready_to_pickup_expiration_hours' => $hours,
            ]);
        }

        return redirect()
            ->route('admin.orders.index')
            ->with('status', $message);
    }

    private function logAuditAction(string $actionType, string $description, array $meta = []): void
    {
        try {
            app(AuditLogger::class)->logAdminAction($actionType, 'orders', $description, $meta);
        } catch (\Throwable $e) {
            Log::warning('Order settings audit log write failed', [
                'action_type' => $actionType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
