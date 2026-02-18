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
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autenticado'], 401);
            }
            $request->session()->put('url.intended', $request->fullUrl());
            return redirect()->route('login.show')->with('error', 'Debes iniciar sesión para acceder.');
        }

        // Verificar si el usuario es administrador
        if (Auth::user()->rol !== 'admin') {
            Auth::logout();
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Acceso denegado. Solo administradores pueden acceder.'], 403);
            }
            $request->session()->put('url.intended', $request->fullUrl());
            return redirect()->route('login.show')->with('error', 'Acceso denegado. Solo administradores pueden acceder al sistema.');
        }

        return $next($request);
    }
}