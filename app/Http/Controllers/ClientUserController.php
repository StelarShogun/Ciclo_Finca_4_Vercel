<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

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

        try {
            $client = DB::table('client_table')->where('gmail', $request->gmail)->first();
            if ($client && Hash::check($request->password, $client->password)) {
                session([
                    'client_id' => $client->user_id,
                    'client_name' => $client->name,
                    'client_first_surname' => $client->first_surname,
                    'client_second_surname' => $client->second_surname,
                ]);
                return redirect()->route('clientes.catalogo');
            }
            return back()->withErrors(['gmail' => 'Correo o contraseña incorrectos'])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['gmail' => 'Error interno: ' . $e->getMessage()])->withInput();
        }
    }

    // Logout
    public function logout(Request $request)
    {
        $request->session()->forget('client_id');
        return redirect()->route('clients.login');
    }
}
