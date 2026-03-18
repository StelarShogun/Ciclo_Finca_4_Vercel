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

    // Display the authenticated client's profile.
    public function show()
    {
        $client = Auth::guard('clients')->user();
        return view('clients.profile', compact('client'));
    }

    // Update the authenticated client's personal data.
    public function update(Request $request)
    {
        $client = Auth::guard('clients')->user();

        $request->validate([
            'name'           => 'required|string|min:2|max:60',
            'first_surname'  => 'required|string|min:2|max:60',
            'second_surname' => 'nullable|string|max:60',
            // Unique rule ignoring the client's own record
            'gmail'          => 'required|email|max:100|unique:client_table,gmail,' . $client->user_id . ',user_id',
        ], [
            'name.required'          => 'El nombre es obligatorio.',
            'name.min'               => 'El nombre debe tener al menos 2 caracteres.',
            'name.max'               => 'El nombre no puede superar los 60 caracteres.',
            'first_surname.required' => 'El primer apellido es obligatorio.',
            'first_surname.min'      => 'El primer apellido debe tener al menos 2 caracteres.',
            'first_surname.max'      => 'El primer apellido no puede superar los 60 caracteres.',
            'second_surname.max'     => 'El segundo apellido no puede superar los 60 caracteres.',
            'gmail.required'         => 'El correo electrónico es obligatorio.',
            'gmail.email'            => 'El formato del correo electrónico no es válido.',
            'gmail.max'              => 'El correo electrónico no puede superar los 100 caracteres.',
            'gmail.unique'           => 'Este correo electrónico ya está registrado.',
        ]);

        DB::table('client_table')
            ->where('user_id', $client->user_id)
            ->update([
                'name'           => $request->name,
                'first_surname'  => $request->first_surname,
                'second_surname' => $request->second_surname,
                'gmail'          => $request->gmail,
                'updated_at'     => now(),
            ]);

        // Reflect the updated name in the active session
        session([
            'client_name'           => $request->name,
            'client_first_surname'  => $request->first_surname,
            'client_second_surname' => $request->second_surname,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cambios guardados correctamente.',
            ]);
        }

        return redirect()->route('clients.profile')->with('profile_updated', true);
    }

    // Update (or set for the first time) the client's password.
    // A client is 'Google-only' when they have a google_id but no local password.
    public function updatePassword(Request $request)
    {
        $client = Auth::guard('clients')->user();

        $isGoogleOnly = $client->google_id && empty($client->password);

        // Google-only accounts do not need to verify a current password
        $rules = [
            'new_password' => 'required|string|min:8|confirmed',
        ];

        if (!$isGoogleOnly) {
            $rules['current_password'] = 'required|string';
        }

        $messages = [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'new_password.required'     => 'La nueva contraseña es obligatoria.',
            'new_password.min'          => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed'    => 'Las contraseñas no coinciden.',
        ];

        $request->validate($rules, $messages);

        // Verify the current password only for local accounts
        if (!$isGoogleOnly) {
            if (!Hash::check($request->current_password, $client->password)) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La contraseña actual no es correcta.',
                        'errors'  => ['current_password' => ['La contraseña actual no es correcta.']],
                    ], 422);
                }

                return back()
                    ->withErrors(['current_password' => 'La contraseña actual no es correcta.'])
                    ->withInput();
            }
        }

        DB::table('client_table')
            ->where('user_id', $client->user_id)
            ->update([
                'password'   => Hash::make($request->new_password),
                'updated_at' => now(),
            ]);

        $message = $isGoogleOnly
            ? 'Contraseña definida. Ahora puedes iniciar sesión con correo y contraseña.'
            : 'Contraseña actualizada correctamente.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        $sessionKey = $isGoogleOnly ? 'password_defined' : 'password_updated';
        return redirect()->route('clients.profile')->with($sessionKey, true);
    }

    // ============================================================
    // AUTHENTICATION
    // ============================================================

    // Show the login form.
    public function showLoginForm()
    {
        return view('clients_users.login_user');
    }

    // Process a login attempt.
    public function login(Request $request)
    {
        $rules = [
            'gmail'    => 'required|email',
            'password' => 'required',
        ];

        // Add reCAPTCHA validation only if the site key is configured
        if (config('recaptcha.site_key')) {
            $rules['g-recaptcha-response'] = ['required', new Recaptcha()];
        }

        $request->validate($rules, [
            'gmail.required'    => 'El correo electrónico es obligatorio.',
            'gmail.email'       => 'El formato del correo electrónico no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $credentials = [
            'gmail'    => $request->gmail,
            'password' => $request->password,
        ];
        $remember = $request->has('remember');

        if (Auth::guard('clients')->attempt($credentials, $remember)) {
            $client = Auth::guard('clients')->user();
            // Store client data in the session for use in views
            session([
                'client_id'             => $client->user_id,
                'client_name'           => $client->name,
                'client_first_surname'  => $client->first_surname,
                'client_second_surname' => $client->second_surname,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'  => true,
                    'redirect' => route('clients.catalog'),
                    'message'  => 'Inicio de sesión exitoso.',
                ]);
            }

            return redirect()->route('clients.catalog');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Correo o contraseña incorrectos.',
            ], 401);
        }

        return back()->withErrors(['gmail' => 'Correo o contraseña incorrectos.'])->withInput();
    }

    // Log the client out and invalidate the session token.
    public function logout(Request $request)
    {
        Auth::guard('clients')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.show')->with('status', 'Sesión cerrada correctamente.');
    }
}