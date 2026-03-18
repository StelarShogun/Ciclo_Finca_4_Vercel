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

            // Bloquear acceso si el correo no ha sido verificado
            if (!$client->email_verified) {
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
            $mailWarning = 'No se pudo enviar el correo automáticamente. Usa el código: ' . $code;
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

        return redirect()->route('clientes.catalogo')->with('success', '¡Cuenta verificada y creada exitosamente!');
    }

    // Resend verification code
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

    // Logout
    public function logout(Request $request)
    {
        Auth::guard('clients')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login.show')->with('status', 'Sesión cerrada correctamente.');
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

            return redirect()->route('clientes.home')->with('status', 'Inicio de sesión exitoso con Google');
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
            return redirect()->route('clientes.home')->with('error', $message);
        }
    }
}
