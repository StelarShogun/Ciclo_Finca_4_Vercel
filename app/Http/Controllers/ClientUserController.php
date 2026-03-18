<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Client;
use App\Rules\Recaptcha;

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
        $rules = [
            'gmail' => 'required|email',
            'password' => 'required',
        ];
        if (config('recaptcha.site_key')) {
            $rules['g-recaptcha-response'] = ['required', new Recaptcha()];
        }
        $request->validate($rules);

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

    // Show register form
    public function showRegisterForm()
    {
        return view('clients_users.create');
    }

    // Process registration
    public function register(Request $request)
    {
        $request->validate(
            [
                'name'                  => ['required', 'string', 'max:50', 'min:2', 'regex:/^[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+$/u'],
                'first_surname'         => ['required', 'string', 'max:50', 'min:2', 'regex:/^[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+$/u'],
                'second_surname'        => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+$/u'],
                'gmail'                 => ['required', 'email', 'unique:client_table,gmail', 'regex:/^[^@]+@gmail\.com$/i'],
                'password'              => ['required', 'string', 'min:8', 'confirmed'],
            ],
            [
                'name.required'             => 'El nombre es obligatorio.',
                'name.min'                  => 'El nombre debe tener al menos 2 caracteres.',
                'name.max'                  => 'El nombre no puede superar 50 caracteres.',
                'name.regex'                => 'El nombre solo puede contener letras y espacios.',
                'first_surname.required'    => 'El apellido es obligatorio.',
                'first_surname.min'         => 'El apellido debe tener al menos 2 caracteres.',
                'first_surname.max'         => 'El apellido no puede superar 50 caracteres.',
                'first_surname.regex'       => 'El apellido solo puede contener letras y espacios.',
                'second_surname.max'        => 'El segundo apellido no puede superar 50 caracteres.',
                'second_surname.regex'      => 'El segundo apellido solo puede contener letras y espacios.',
                'gmail.required'            => 'El correo Gmail es obligatorio.',
                'gmail.email'               => 'Debe ingresar un correo electrónico válido.',
                'gmail.unique'              => 'Este correo ya está registrado.',
                'gmail.regex'               => 'Solo se aceptan correos de Gmail (@gmail.com).',
                'password.required'         => 'La contraseña es obligatoria.',
                'password.min'              => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed'        => 'Las contraseñas no coinciden.',
            ]
        );

        $client = Client::create([
            'name'           => $request->name,
            'first_surname'  => $request->first_surname,
            'second_surname' => $request->second_surname,
            'gmail'          => strtolower($request->gmail),
            'password'       => Hash::make($request->password),
        ]);

        Auth::guard('clients')->login($client);

        session([
            'client_id'            => $client->user_id,
            'client_name'          => $client->name,
            'client_first_surname' => $client->first_surname,
            'client_second_surname'=> $client->second_surname,
        ]);

        return redirect()->route('clientes.catalogo')->with('success', '¡Cuenta creada exitosamente!');
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
