<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Rules\Recaptcha;
use Laravel\Socialite\Facades\Socialite;

class ClientUserController extends Controller
{
    // ============================================================
    // PROFILE
    // ============================================================

    // Display the authenticated client's profile, normalizing provider if null.
    public function show()
    {
        $client = Auth::guard('clients')->user();

        if (empty($client->provider) || $client->getRawOriginal('provider') === null) {
            DB::table('client_table')
                ->where('user_id', $client->user_id)
                ->update(['provider' => 'local']);

            $client->provider = 'local';
        }

        $isGoogleOnly = ($client->provider ?? 'local') === 'google';

        return view('clients.profile', compact('client', 'isGoogleOnly'));
    }

    // Update the authenticated client's personal data.
    public function update(Request $request)
    {
        $client = Auth::guard('clients')->user();

        $request->validate([
            'name'           => 'required|string|min:2|max:60',
            'first_surname'  => 'required|string|min:2|max:60',
            'second_surname' => 'nullable|string|max:60',
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

    // Update the client's password; switches Google accounts to local provider.
    public function updatePassword(Request $request)
    {
        $client   = Auth::guard('clients')->user();
        $isGoogle = ($client->provider ?? 'local') === 'google';

        $rules = ['new_password' => 'required|string|min:8|confirmed'];

        if (!$isGoogle) {
            $rules['current_password'] = 'required|string';
        }

        $messages = [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'current_password.string'   => 'La contraseña actual no es válida.',
            'new_password.required'     => 'La nueva contraseña es obligatoria.',
            'new_password.min'          => 'La contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed'    => 'Las contraseñas no coinciden.',
        ];

        $request->validate($rules, $messages);

        // Verify current password and prevent reuse for local accounts.
        if (!$isGoogle) {
            if (!Hash::check($request->current_password, $client->password)) {
                $error = ['current_password' => ['La contraseña actual no es correcta.']];

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }

                return back()->withErrors($error)->withInput();
            }

            if (Hash::check($request->new_password, $client->password)) {
                $error = ['new_password' => ['La nueva contraseña no puede ser igual a la actual.']];

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }

                return back()->withErrors($error)->withInput();
            }
        }

        DB::table('client_table')
            ->where('user_id', $client->user_id)
            ->update([
                'password'   => Hash::make($request->new_password),
                'provider'   => 'local',
                'updated_at' => now(),
            ]);

        // Use specific flash keys so the JS can trigger the correct alert message.
        $flashKey = $isGoogle ? 'password_defined' : 'password_updated';
        $message  = $isGoogle
            ? 'Contraseña definida. Ya puedes iniciar sesión con tu correo y contraseña.'
            : 'Contraseña actualizada correctamente.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'          => true,
                'message'          => $message,
                'provider_changed' => $isGoogle,
            ]);
        }

        return redirect()->route('clients.profile')->with($flashKey, true);
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
        $remember = $request->boolean('remember');

        if (Auth::guard('clients')->attempt($credentials, $remember)) {
            $client = Auth::guard('clients')->user();

            // Bloquear acceso si el usuario está baneado
            if ($client->active === false) {
                Auth::guard('clients')->logout();
                $msg = 'En este momento se encuentra baneado, contactar con el administrador para más información.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 403);
                }
                return back()->withErrors(['gmail' => $msg])->withInput();
            }

            // Bloquear acceso si el correo no ha sido verificado (solo cuando la columna existe y es false)
            if ($client->email_verified === false) {
                Auth::guard('clients')->logout();

                // Generar y enviar código de verificación automáticamente
                $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires = now()->addMinutes(10);
                $client->update([
                    'verification_code'            => $code,
                    'verification_code_expires_at' => $expires,
                ]);
                try {
                    Mail::raw(
                        "Hola {$client->name},\n\nTu código de verificación es: {$code}\n\nExpira en 10 minutos.",
                        function ($message) use ($client) {
                            $message->to($client->gmail)
                                    ->subject('Código de verificación - Ciclo Finca');
                        }
                    );
                } catch (\Exception $e) {
                    // El correo falló pero igual redirigimos; el usuario puede reenviar
                }

                session([
                    'pending_client_id' => $client->user_id,
                    'pending_gmail'     => $client->gmail,
                ]);
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success'  => false,
                        'redirect' => route('clients.verify.form'),
                        'message'  => 'Debes verificar tu correo antes de iniciar sesión.',
                    ], 403);
                }
                return redirect()->route('clients.verify.form')
                    ->withErrors(['gmail' => 'Debes verificar tu correo antes de iniciar sesión.']);
            }

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

        return back()
            ->withErrors(['gmail' => 'Correo o contraseña incorrectos.'])
            ->withInput();
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

        $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(10);

        $client = Client::create([
            'name'                         => $request->name,
            'first_surname'                => $request->first_surname,
            'second_surname'               => $request->second_surname,
            'gmail'                        => strtolower($request->gmail),
            'password'                     => Hash::make($request->password),
            'verification_code'            => $code,
            'verification_code_expires_at' => $expires,
            'email_verified'               => false,
        ]);

        session([
            'pending_client_id' => $client->user_id,
            'pending_gmail'     => $client->gmail,
        ]);

        $mailWarning = null;
        try {
            Mail::raw(
                "Hola {$client->name},\n\nTu código de verificación es: {$code}\n\nExpira en 10 minutos.\n\nSi no creaste esta cuenta, ignora este correo.",
                function ($message) use ($client) {
                    $message->to($client->gmail)
                            ->subject('Código de verificación - Ciclo Finca');
                }
            );
        } catch (\Exception $e) {
            $mailWarning = 'No se pudo enviar el correo automáticamente. Por favor, usa la opción «Reenviar código» para intentarlo de nuevo.';
        }

        return redirect()->route('clients.verify.form')
            ->with('pending_gmail', $client->gmail)
            ->with('mail_warning', $mailWarning);
    }

    // Show verify form
    public function showVerifyForm()
    {
        if (!session('pending_client_id')) {
            return redirect()->route('clients.register.form');
        }
        return view('clients_users.verify_gmail_code');
    }

    // Process verification code
    public function verify(Request $request)
    {
        $request->validate(
            ['verification_code' => 'required|digits:6'],
            [
                'verification_code.required' => 'El código es obligatorio.',
                'verification_code.digits'   => 'El código debe tener exactamente 6 dígitos.',
            ]
        );

        $clientId = session('pending_client_id');
        if (!$clientId) {
            return redirect()->route('clients.register.form')
                ->withErrors(['verification_code' => 'Sesión expirada. Regístrate de nuevo.']);
        }

        $client = Client::find($clientId);

        if (!$client || $client->verification_code !== $request->verification_code) {
            return back()->withErrors(['verification_code' => 'Código incorrecto. Inténtalo de nuevo.']);
        }

        if (now()->isAfter($client->verification_code_expires_at)) {
            return back()->withErrors(['verification_code' => 'El código ha expirado. Solicita uno nuevo.']);
        }

        $client->update([
            'email_verified'               => true,
            'verification_code'            => null,
            'verification_code_expires_at' => null,
        ]);

        session()->forget(['pending_client_id', 'pending_gmail']);

        Auth::guard('clients')->login($client);

        session([
            'client_id'             => $client->user_id,
            'client_name'           => $client->name,
            'client_first_surname'  => $client->first_surname,
            'client_second_surname' => $client->second_surname,
        ]);

        return redirect()->route('clients.catalog')->with('success', '¡Cuenta verificada y creada exitosamente!');
    }

    // Resend verification code
    public function resendCode(Request $request)
    {
        $clientId = session('pending_client_id');
        if (!$clientId) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Sesión expirada.'], 422);
            }
            return redirect()->route('clients.register.form');
        }

        $client = Client::find($clientId);
        if (!$client) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Cliente no encontrado.'], 422);
            }
            return redirect()->route('clients.register.form');
        }

        $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(10);

        $client->update([
            'verification_code'            => $code,
            'verification_code_expires_at' => $expires,
        ]);

        $mailWarning = null;
        try {
            Mail::raw(
                "Hola {$client->name},\n\nTu nuevo código de verificación es: {$code}\n\nExpira en 10 minutos.",
                function ($message) use ($client) {
                    $message->to($client->gmail)
                            ->subject('Nuevo código de verificación - Ciclo Finca');
                }
            );
        } catch (\Exception $e) {
            $mailWarning = 'No se pudo enviar el correo. Por favor, intenta reenviar el código nuevamente.';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Código reenviado correctamente.']);
        }

        return redirect()->route('clients.verify.form')
            ->with('pending_gmail', $client->gmail)
            ->with('mail_warning', $mailWarning)
            ->with('success', 'Se ha enviado un nuevo código a tu correo.');
    }

    // Log the client out and invalidate the session.
    public function logout(Request $request)
    {
        Auth::guard('clients')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login.show')
            ->with('status', 'Sesión cerrada correctamente.');
    }

    // Show the password-recovery form.
    public function showRecoveryForm()
    {
        return view('clients_users.recovery');
    }

    // Recovery step 1: validate form, send verification code, store pending password in session.
    public function resetPassword(Request $request)
    {
        $request->validate(
            [
                'gmail'                     => 'required|email',
                'new_password'              => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string',
            ],
            [
                'gmail.required'                     => 'El correo es obligatorio.',
                'gmail.email'                        => 'Ingresa un correo válido.',
                'new_password.required'              => 'La nueva contraseña es obligatoria.',
                'new_password.min'                   => 'La contraseña debe tener al menos 8 caracteres.',
                'new_password.confirmed'             => 'Las contraseñas no coinciden.',
                'new_password_confirmation.required' => 'Debes confirmar la nueva contraseña.',
            ]
        );

        $client = Client::where('gmail', strtolower($request->gmail))->first();

        // Always respond with the same message to avoid email enumeration.
        // If the client doesn't exist, redirect to the verify form anyway.
        if (!$client) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'            => true,
                    'needs_verification' => true,
                    'redirect'           => route('clients.recovery.verify.form'),
                    'message'            => 'Si el correo existe, se ha enviado un código de verificación.',
                ]);
            }
            return redirect()->route('clients.recovery.verify.form');
        }

        $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(15);

        // Store pending password hash in session — only written to DB after code is verified.
        session([
            'pending_recovery_id'       => $client->user_id,
            'pending_recovery_gmail'    => $client->gmail,
            'pending_recovery_password' => Hash::make($request->new_password),
        ]);

        DB::table('client_table')
            ->where('user_id', $client->user_id)
            ->update([
                'verification_code'            => $code,
                'verification_code_expires_at' => $expires,
            ]);

        try {
            Mail::raw(
                "Hola {$client->name},\n\nTu código de verificación para recuperar tu contraseña es: {$code}\n\nExpira en 15 minutos.\n\nSi no solicitaste este cambio, ignora este correo.",
                function ($message) use ($client) {
                    $message->to($client->gmail)
                            ->subject('Código de recuperación de contraseña - Ciclo Finca');
                }
            );
        } catch (\Exception $e) {
            Log::error('Recovery mail failed: ' . $e->getMessage());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'            => true,
                'needs_verification' => true,
                'redirect'           => route('clients.recovery.verify.form'),
                'message'            => 'Se ha enviado un código de verificación a tu correo.',
            ]);
        }

        return redirect()->route('clients.recovery.verify.form')
            ->with('pending_gmail', $client->gmail);
    }

    // Recovery step 2: show code-entry form.
    public function showRecoveryVerifyForm()
    {
        if (!session('pending_recovery_id')) {
            return redirect()->route('clients.recovery.form');
        }
        return view('clients_users.verify_gmail_code');
    }

    // Recovery step 3: verify code and write the new password to DB.
    public function verifyRecoveryAndReset(Request $request)
    {
        $request->validate(
            ['verification_code' => 'required|digits:6'],
            [
                'verification_code.required' => 'El código es obligatorio.',
                'verification_code.digits'   => 'El código debe tener exactamente 6 dígitos.',
            ]
        );

        $clientId = session('pending_recovery_id');
        if (!$clientId) {
            $msg = 'Sesión expirada. Vuelve a intentar la recuperación.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return redirect()->route('clients.recovery.form')->withErrors(['verification_code' => $msg]);
        }

        $client = Client::find($clientId);
        if (!$client || $client->verification_code !== $request->verification_code) {
            $msg = 'Código incorrecto. Inténtalo de nuevo.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return back()->withErrors(['verification_code' => $msg]);
        }

        if (now()->isAfter($client->verification_code_expires_at)) {
            $msg = 'El código ha expirado. Vuelve a solicitar la recuperación.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return redirect()->route('clients.recovery.form')->withErrors(['verification_code' => $msg]);
        }

        $hashedPassword = session('pending_recovery_password');

        DB::table('client_table')
            ->where('user_id', $client->user_id)
            ->update([
                'password'                     => $hashedPassword,
                'provider'                     => 'local',
                'verification_code'            => null,
                'verification_code_expires_at' => null,
                'updated_at'                   => now(),
            ]);

        session()->forget(['pending_recovery_id', 'pending_recovery_gmail', 'pending_recovery_password']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
        }

        return redirect()->route('login.show')->with('status', 'Contraseña actualizada. Ya puedes iniciar sesión.');
    }

    /**
     * Redirigir a Google para autenticación OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Callback de Google OAuth. Guarda en client_table solo por gmail (sin provider/provider_id).
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $email = strtolower($googleUser->email);

            $client = Client::where('gmail', $email)->first();

            if ($client) {
                // Bloquear acceso si el usuario está baneado
                if ($client->active === false) {
                    $msg = 'En este momento se encuentra baneado, contactar con el administrador para más información.';
                    return redirect()->route('clients.home')->with('error', $msg);
                }

                // Actualizar email_verified solo si la columna existe
                if (Schema::hasColumn((new Client())->getTable(), 'email_verified')) {
                    $client->update(['email_verified' => true]);
                }
            } else {
                $partes = array_filter(explode(' ', trim($googleUser->name ?? ''), 3));
                $nombre = $partes[0] ?? $googleUser->name ?? 'Usuario';
                $apellido1 = $partes[1] ?? '-';
                $apellido2 = $partes[2] ?? null;
                $data = [
                    'name'          => $nombre,
                    'first_surname' => $apellido1,
                    'second_surname' => $apellido2,
                    'gmail'         => $email,
                    'password'      => Hash::make(Str::random(32)),
                ];
                if (Schema::hasColumn((new Client())->getTable(), 'email_verified')) {
                    $data['email_verified'] = true;
                }
                $client = Client::create($data);
            }

            Auth::guard('clients')->login($client);
            $request->session()->regenerate();
            session([
                'client_id'            => $client->user_id,
                'client_name'          => $client->name,
                'client_first_surname'  => $client->first_surname,
                'client_second_surname' => $client->second_surname,
            ]);

            return redirect()->route('clients.home')->with('status', 'Inicio de sesión exitoso con Google');
        } catch (\Throwable $e) {
            $detail = $e->getMessage() ?: get_class($e) . ' en ' . $e->getFile() . ':' . $e->getLine();
            Log::error('Error en Google OAuth: ' . $detail, [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);
            $message = config('app.debug')
                ? 'Error al iniciar sesión con Google: ' . $detail
                : 'Error al iniciar sesión con Google. Revisa storage/logs/laravel.log';
            return redirect()->route('clients.home')->with('error', $message);
        }
    }
}
