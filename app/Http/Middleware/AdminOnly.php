<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Usar el guard 'admin' para verificar si un administrador está autenticado
        if (!Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autenticado'], 401);
            }
            // Guardar la URL a la que se intentaba acceder
            $request->session()->put('url.intended', $request->fullUrl());
            
            // Redirigir a la ruta de login de admin
            return redirect()->route('admin.login')->with('error', 'Acceso denegado. Debes iniciar sesión como administrador.');
        }

        // Si el guard 'admin' pasa, el usuario es un administrador.
        // La comprobación de rol ya no es necesaria aquí.
        return $next($request);
    }
}