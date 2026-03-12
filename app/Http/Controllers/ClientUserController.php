<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Client;

class ClientUserController extends Controller
{
    // Show login form
    public function showLoginForm()
    {
        return view('clients_users.login_user');
    }

    // Process login
    public function login(Request $request)
    {
        $request->validate([
            'gmail' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = [
            'gmail' => $request->gmail,
            'password' => $request->password,
        ];
        $remember = $request->has('remember');

        if (Auth::guard('clients')->attempt($credentials, $remember)) {
            $client = Auth::guard('clients')->user();
            session([
                'client_id' => $client->user_id,
                'client_name' => $client->name,
                'client_first_surname' => $client->first_surname,
                'client_second_surname' => $client->second_surname,
            ]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => route('clientes.catalogo'),
                    'message' => 'Inicio de sesión exitoso'
                ]);
            }
            return redirect()->route('clientes.catalogo');
        }
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Correo o contraseña incorrectos'
            ], 401);
        }
        return back()->withErrors(['gmail' => 'Correo o contraseña incorrectos'])->withInput();
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::guard('clients')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login.show')->with('status', 'Sesión cerrada correctamente.');
    }
}
