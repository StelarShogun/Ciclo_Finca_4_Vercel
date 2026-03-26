<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autenticado'], 401);
            }
            // Store the intended URL so the admin can be redirected back after login
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('admin.login')->with('error', 'Acceso denegado. Debes iniciar sesión como administrador.');
        }

        return $next($request);
    }
}