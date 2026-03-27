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
    public function show()
    {
        $client = Auth::guard('clients')->user();
        $isGoogleOnly = $client->provider === 'google';

        return view('client.profile', compact('client', 'isGoogleOnly'));
    }

    public function update(Request $request)
    {
        $client = Auth::guard('clients')->user();

        $request->validate([
            'name'           => 'required|string|min:2|max:60',
            'first_surname'  => 'required|string|min:2|max:60',
            'second_surname' => 'nullable|string|max:60',
            // Exclude current user from unique check to allow saving without changing email
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

        // Keep session data in sync so the UI reflects changes immediately
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

    public function updatePassword(Request $request)
    {
        $client   = Auth::guard('clients')->user();
        $isGoogle = ($client->provider ?? 'local') === 'google';

        // Google-linked accounts have no existing password to verify
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

        if (!$isGoogle) {
            if (!Hash::check($request->current_password, $client->password)) {
                $error = ['current_password' => ['La contraseña actual no es correcta.']];

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }
                return back()->withErrors($error)->withInput();
            }

            // Prevent silent no-op updates where the password does not actually change
            if (Hash::check($request->new_password, $client->password)) {
                $error = ['new_password' => ['La nueva contraseña no puede ser igual a la actual.']];

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }
                return back()->withErrors($error)->withInput();
            }
        }

        // Setting a password on a Google account converts it to local authentication
        DB::table('client_table')
            ->where('user_id', $client->user_id)
            ->update([
                'password'   => Hash::make($request->new_password),
                'provider'   => 'local',
                'updated_at' => now(),
            ]);

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

    public function showLoginForm()
    {
        return view('client.login');
    }

    public function login(Request $request)
    {
        $rules = [
            'gmail'    => 'required|email',
            'password' => 'required',
        ];

        // Only enforce reCAPTCHA when the site key is configured in the environment
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

            if ($client->active === false) {
                Auth::guard('clients')->logout();
                $msg = 'En este momento se encuentra baneado, contactar con el administrador para más información.';

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 403);
                }
                return back()->withErrors(['gmail' => $msg])->withInput();
            }

            // Force unverified users through the email verification flow before granting access
            if ($client->email_verified === false) {
                Auth::guard('clients')->logout();
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

    public function showRegisterForm()
    {
        return view('client.register');
    }

    public function register(Request $request)
    {
        $request->validate(
            [
                'name'           => ['required', 'string', 'max:50', 'min:2', 'regex:/^[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+$/u'],
                'first_surname'  => ['required', 'string', 'max:50', 'min:2', 'regex:/^[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+$/u'],
                'second_surname' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+$/u'],
                // Restrict registration to Gmail addresses only
                'gmail'          => ['required', 'email', 'unique:client_table,gmail', 'regex:/^[^@]+@gmail\.com$/i'],
                'password'       => ['required', 'string', 'min:8', 'confirmed'],
            ],
            [
                'name.required'          => 'El nombre es obligatorio.',
                'name.min'               => 'El nombre debe tener al menos 2 caracteres.',
                'name.max'               => 'El nombre no puede superar 50 caracteres.',
                'name.regex'             => 'El nombre solo puede contener letras y espacios.',
                'first_surname.required' => 'El apellido es obligatorio.',
                'first_surname.min'      => 'El apellido debe tener al menos 2 caracteres.',
                'first_surname.max'      => 'El apellido no puede superar 50 caracteres.',
                'first_surname.regex'    => 'El apellido solo puede contener letras y espacios.',
                'second_surname.max'     => 'El segundo apellido no puede superar 50 caracteres.',
                'second_surname.regex'   => 'El segundo apellido solo puede contener letras y espacios.',
                'gmail.required'         => 'El correo Gmail es obligatorio.',
                'gmail.email'            => 'Debe ingresar un correo electrónico válido.',
                'gmail.unique'           => 'Este correo ya está registrado.',
                'gmail.regex'            => 'Solo se aceptan correos de Gmail (@gmail.com).',
                'password.required'      => 'La contraseña es obligatoria.',
                'password.min'           => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed'     => 'Las contraseñas no coinciden.',
            ]
        );

        // Zero-padded to guarantee exactly 6 digits; expires after 10 minutes
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

        // Expose the code in a flash message as a fallback if mail delivery fails
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
            $mailWarning = 'No se pudo enviar el correo automáticamente. Usa el código: ' . $code;
        }

        return redirect()->route('clients.verify.form')
            ->with('pending_gmail', $client->gmail)
            ->with('mail_warning', $mailWarning);
    }

    public function showVerifyForm()
    {
        // Redirect users who land here without going through registration first
        if (!session('pending_client_id')) {
            return redirect()->route('clients.register.form');
        }
        return view('client.verify_gmail_code');
    }

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

        // Clear the one-time code after successful verification to prevent reuse
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

    public function resendCode()
    {
        $clientId = session('pending_client_id');
        if (!$clientId) {
            return redirect()->route('clients.register.form');
        }

        $client = Client::find($clientId);
        if (!$client) {
            return redirect()->route('clients.register.form');
        }

        // Invalidate the previous code by issuing a new one with a fresh expiry window
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
            $mailWarning = 'No se pudo enviar el correo. Usa el código: ' . $code;
        }

        return redirect()->route('clients.verify.form')
            ->with('pending_gmail', $client->gmail)
            ->with('mail_warning', $mailWarning)
            ->with('success', 'Se ha enviado un nuevo código a tu correo.');
    }

    public function logout(Request $request)
    {
        Auth::guard('clients')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login.show')
            ->with('status', 'Sesión cerrada correctamente.');
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $email = strtolower($googleUser->email);

            $client = Client::where('gmail', $email)->first();

            if ($client) {
                if ($client->active === false) {
                    $msg = 'En este momento se encuentra baneado, contactar con el administrador para más información.';
                    return redirect()->route('clients.home')->with('error', $msg);
                }

                // Google-authenticated users are trusted as verified by the provider
                if (Schema::hasColumn((new Client())->getTable(), 'email_verified')) {
                    $client->update(['email_verified' => true]);
                }
            } else {
                // Best-effort split: Google names may have 1, 2, or 3+ parts
                $partes    = array_filter(explode(' ', trim($googleUser->name ?? ''), 3));
                $nombre    = $partes[0] ?? $googleUser->name ?? 'Usuario';
                $apellido1 = $partes[1] ?? '-';
                $apellido2 = $partes[2] ?? null;

                $data = [
                    'name'           => $nombre,
                    'first_surname'  => $apellido1,
                    'second_surname' => $apellido2,
                    'gmail'          => $email,
                    // Unusable random password; account uses OAuth until a local password is set
                    'password'       => Hash::make(Str::random(32)),
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

            Log::error('Google OAuth error: ' . $detail, [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            // Expose full error details only in debug mode to avoid leaking internals
            $message = config('app.debug')
                ? 'Error al iniciar sesión con Google: ' . $detail
                : 'Error al iniciar sesión con Google. Revisa storage/logs/laravel.log';

            return redirect()->route('clients.home')->with('error', $message);
        }
    }

    public function showRecoveryForm()
    {
        return view('client.recovery');
    }

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
                        ->subject('Código de recuperación de contraseña - Ciclo Finca 4');
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

    public function showRecoveryVerifyForm()
    {
        if (!session('pending_recovery_id')) {
            return redirect()->route('clients.recovery.form');
        }
        return view('client.verify_gmail_code');
    }

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
}
