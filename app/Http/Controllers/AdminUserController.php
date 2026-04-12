<?php

namespace App\Http\Controllers;

use App\Rules\Recaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'g-recaptcha-response' => ['required', new Recaptcha],
        ], [
            'g-recaptcha-response.required' => 'Por favor completa el reCAPTCHA.',
        ]);

        $credentials = $request->only('gmail', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            // Authentication passed...
            $user = Auth::guard('admin')->user();
            $user->last_access = now();
            $user->save();

            return redirect()->route('dashboard');
        }

        return redirect()->back()->withInput($request->only('gmail'))->withErrors([
            'gmail' => 'Usuario no existe o credenciales inválidas',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
