<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        AppSetting::setOrderExpirationDays($days);

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
}
