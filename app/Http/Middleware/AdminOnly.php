<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autenticado'], 401);
            }
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('admin.login')->with('error', 'Acceso denegado. Debes iniciar sesión como administrador.');
        }

        $timeout = 86400; // 24 hours in seconds
        $lastActivity = session('admin_last_activity');

        if ($lastActivity && ! is_int($lastActivity)) {
            session()->forget('admin_last_activity');
            $lastActivity = null;
        }

        if ($lastActivity && (time() - $lastActivity) > $timeout) {
            Auth::guard('admin')->logout();
            session()->forget('admin_last_activity');

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Sesión expirada por inactividad.'], 401);
            }

            return redirect()->route('admin.login')
                ->with('error', 'Sesión expirada por inactividad.');
        }

        session(['admin_last_activity' => time()]);

        return $next($request);
    }
}
