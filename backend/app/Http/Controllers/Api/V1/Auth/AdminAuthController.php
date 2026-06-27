<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Rules\Recaptcha;
use App\Services\Admin\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Auth de admin para el SPA Next (Sanctum cookie). Equivalente JSON del login
 * web (App\Http\Controllers\Admin\Auth\AdminUserController) reusando guard
 * 'admin', AuditLogger y la regla Recaptcha. El SPA pide antes /sanctum/csrf-cookie.
 */
final class AdminAuthController extends Controller
{
    public function login(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        // Un cliente logueado no puede tomar sesión de admin.
        if (Auth::guard('clients')->check()) {
            return response()->json([
                'message' => 'No tenés permiso para acceder al panel de administración.',
            ], 403);
        }

        $rules = [
            'gmail' => ['required', 'email'],
            'password' => ['required'],
        ];
        if (config('services.recaptcha.key')) {
            $rules['g-recaptcha-response'] = ['required', new Recaptcha];
        }
        $request->validate($rules, [
            'g-recaptcha-response.required' => 'Por favor completa el reCAPTCHA.',
        ]);

        $credentials = $request->only('gmail', 'password');

        if (! Auth::guard('admin')->attempt($credentials)) {
            $auditLogger->logAdminAction('admin_login_failed', 'auth', 'Admin login failed.', [
                'attempted_gmail' => (string) $request->input('gmail'),
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'gmail' => 'Usuario no existe o credenciales inválidas',
            ]);
        }

        $user = Auth::guard('admin')->user();
        $user->last_access = now();
        $user->save();

        // Evita fijación de sesión tras autenticar.
        $request->session()->regenerate();

        $auditLogger->logAdminAction('admin_login', 'auth', 'Admin user logged in.', [
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ], $user);

        return response()->json([
            'data' => ['type' => 'admin', 'user' => $user],
            'message' => 'Inicio de sesión exitoso.',
        ]);
    }

    public function logout(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        $auditLogger->logAdminAction('admin_logout', 'auth', 'Admin user logged out.', [
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ], $admin);

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
