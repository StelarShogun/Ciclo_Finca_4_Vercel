<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Redirigir cuando no autenticado: guard 'clients' → login de clientes (HU CF4-6).
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }
        $guards = $exception->guards();
        if (in_array('clients', $guards)) {
            return redirect()->route('login.show');
        }
        return redirect()->guest($exception->redirectTo() ?? route('login'));
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token CSRF inválido o expirado. Refresca la página.'
                ], 419);
            }
            return response()->view('errors.419', [], 419);
        }
        return parent::render($request, $exception);
    }
}
