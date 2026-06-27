<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreventDirectAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('admin')->check()) {
            // Invalidate any residual session data left by a previous or expired admin session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Acceso no autorizado',
                    'message' => 'Debes iniciar sesión como administrador',
                ], 401);
            }

            return redirect()->route('admin.login')
                ->with('error', 'Acceso denegado. Debes iniciar sesión como administrador.');
        }

        $response = $next($request);

        // Regenerate the CSRF token after a successful mutating request to prevent token fixation
        // without breaking the current request's CSRF validation which has already passed
        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('delete')) {
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $request->session()->regenerateToken();
            }
        }

        return $response;
    }
}
