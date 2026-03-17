<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login.admin_login');
    }

    public function login(Request $request)
    {
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

    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect('/admin/login');
    }
}
