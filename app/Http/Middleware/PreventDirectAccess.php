<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreventDirectAccess
{
    /**
     * Handle an incoming request.
     * Este middleware previene acceso directo mediante URL manipulation
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo valida contra el guard admin; este middleware se usa en el grupo de rutas admin.
        if (!Auth::guard('admin')->check()) {
            // Limpiar cualquier sesión residual
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Acceso no autorizado',
                    'message' => 'Debes iniciar sesión como administrador'
                ], 401);
            }
            
            return redirect()->route('admin.login')
                ->with('error', 'Acceso denegado. Debes iniciar sesión como administrador.');
        }

        $response = $next($request);
        
        // Regenerar token de sesión DESPUÉS de la verificación CSRF (solo en requests exitosos)
        // Esto evita el error de CSRF token mismatch
        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('delete')) {
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $request->session()->regenerateToken();
            }
        }

        return $response;
    }
}