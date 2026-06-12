<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Rules\Recaptcha;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('clients')->check()) {
            abort(403, 'No tenés permiso para acceder al panel de administración.');
        }

        if (Auth::guard('admin')->check()) {
            return redirect()->route('dashboard');
        }

        return view('admin.login.login');
    }

    public function login(Request $request, AuditLogger $auditLogger)
    {
        if (Auth::guard('clients')->check()) {
            abort(403, 'No tenés permiso para acceder al panel de administración.');
        }

        $rules = [];
        if (config('services.recaptcha.key')) {
            $rules['g-recaptcha-response'] = ['required', new Recaptcha];
        }

        $request->validate($rules, [
            'g-recaptcha-response.required' => 'Por favor completa el reCAPTCHA.',
        ]);

        $credentials = $request->only('gmail', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            // Authentication passed...
            $user = Auth::guard('admin')->user();
            $user->last_access = now();
            $user->save();

            $auditLogger->logAdminAction(
                'admin_login',
                'auth',
                'Admin user logged in.',
                [
                    'ip' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                ],
                $user
            );

            return redirect()->route('dashboard');
        }

        $auditLogger->logAdminAction(
            'admin_login_failed',
            'auth',
            'Admin login failed.',
            [
                'attempted_gmail' => (string) $request->input('gmail'),
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]
        );

        return redirect()->back()->withInput($request->only('gmail'))->withErrors([
            'gmail' => 'Usuario no existe o credenciales inválidas',
        ]);
    }

    public function logout(Request $request, AuditLogger $auditLogger)
    {
        $admin = Auth::guard('admin')->user();

        $auditLogger->logAdminAction(
            'admin_logout',
            'auth',
            'Admin user logged out.',
            [
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ],
            $admin
        );

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
