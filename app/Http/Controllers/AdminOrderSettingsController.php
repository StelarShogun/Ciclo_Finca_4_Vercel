<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminOrderSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'order_expiration_days' => ['required', 'integer', 'min:1'],
        ], [
            'order_expiration_days.required' => 'Indique el número de días.',
            'order_expiration_days.integer' => 'El plazo debe ser un número entero.',
            'order_expiration_days.min' => 'El plazo debe ser mayor que cero.',
        ]);

        $days = (int) $validated['order_expiration_days'];
        $previous = AppSetting::getStoredOrderExpirationDays();
        AppSetting::setOrderExpirationDays($days);
        $this->logAuditAction(
            'client_order_settings_update',
            'Configuración de expiración automática de pedidos actualizada.',
            [
                'from_days' => $previous,
                'to_days' => $days,
            ]
        );

        $message = 'Plazo de cancelación automática actualizado correctamente.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'order_expiration_days' => $days,
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
