<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Usuario;
use App\Notifications\UsuarioRegistrado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class UsuarioController extends Controller
{
    public function index()
    {
        $query = Usuario::query();

        // Filtro de búsqueda
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtro por rol
        if (request('rol')) {
            $query->where('rol', request('rol'));
        }

        $usuarios = $query->paginate(10);

        return view('clients_users.index', compact('usuarios'));
    }

    public function create()
    {
        return view('usuarios.create');
    }

    public function store(Request $request)
    {
        // Validar datos
        $validator = Validator::make(
            $request->all(),
            [
                'nombre' => 'required|string|max:50|min:2',
                'apellido' => 'required|string|max:50|min:2',
                'email' => 'required|email|unique:usuarios,email',
                'password' => 'required|string|min:8|confirmed',
                'rol' => 'required|in:admin,cliente,tecnico,vendedor',
            ],
            [
                'email.unique' => 'Este correo electrónico ya está registrado.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'Debe ser un correo electrónico válido.',
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
                'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
                'apellido.required' => 'El apellido es obligatorio.',
                'apellido.max' => 'El apellido no puede tener más de 50 caracteres.',
                'apellido.min' => 'El apellido debe tener al menos 2 caracteres.',
                'rol.required' => 'El rol es obligatorio.',
                'rol.in' => 'El rol debe ser uno de los siguientes: admin, cliente, tecnico, vendedor.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'La confirmación de la contraseña no coincide.'
            ]
        );

        if ($validator->fails()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $usuario = Usuario::create($request->only('nombre', 'apellido', 'email', 'password', 'rol'));

            // Enviar email de bienvenida
            // $usuario->notify(new UsuarioRegistrado());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Usuario registrado exitosamente.',
                    'data' => $usuario
                ], 201);
            }
            
            return redirect()->route('usuarios.index')->with('success', 'Usuario registrado exitosamente.');
            
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar usuario: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error al registrar usuario: ' . $e->getMessage())->withInput();
        }
    }

    public function storeLogin(Request $request)
    {
        // Validar datos
        $validator = Validator::make(
            $request->all(),
            [
                'nombre' => 'required|string|max:50|min:2',
                'apellido' => 'required|string|max:50|min:2',
                'email' => 'required|email|unique:usuarios,email',
                'password' => 'required|string|min:8|confirmed',
            ],
            [
                'email.unique' => 'Este correo electrónico ya está registrado.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'Debe ser un correo electrónico válido.',
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
                'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
                'apellido.required' => 'El apellido es obligatorio.',
                'apellido.max' => 'El apellido no puede tener más de 50 caracteres.',
                'apellido.min' => 'El apellido debe tener al menos 2 caracteres.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'La confirmación de la contraseña no coincide.'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $rol = 'cliente'; // Asignar rol por defecto
            $request->merge(['rol' => $rol]); 
            $usuario = Usuario::create($request->only('nombre', 'apellido', 'email', 'password', 'rol'));

            // Enviar email de bienvenida
            // $usuario->notify(new UsuarioRegistrado());

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente.',
                'data' => $usuario
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        $usuario = Usuario::find($id);
        
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $usuario
        ]);
    }


    public function edit(string $id)
    {
        $usuario = Usuario::find($id);
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(Request $request, string $id)

    {
        try {
            $usuario = Usuario::find($id);
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Validar datos 
            $validator = Validator::make(
                $request->all(),
                [
                    'nombre' => 'required|string|max:50|min:2',
                    'apellido' => 'required|string|max:50|min:2',
                    'email' => 'required|email|unique:usuarios,email,' . $usuario->usuario_id . ',usuario_id',
                    'password' => 'nullable|string|min:8|confirmed',
                    'rol' => 'required|in:admin,cliente,tecnico,vendedor',
                ],
                [
                    'email.unique' => 'Este correo electrónico ya está registrado.',
                    'email.required' => 'El correo electrónico es obligatorio.',
                    'email.email' => 'Debe ser un correo electrónico válido.',
                    'nombre.required' => 'El nombre es obligatorio.',
                    'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
                    'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
                    'apellido.required' => 'El apellido es obligatorio.',
                    'apellido.max' => 'El apellido no puede tener más de 50 caracteres.',
                    'apellido.min' => 'El apellido debe tener al menos 2 caracteres.',
                    'rol.required' => 'El rol es obligatorio.',
                    'rol.in' => 'El rol debe ser uno de los siguientes: admin, cliente, tecnico, vendedor.',
                    'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                    'password.confirmed' => 'La confirmación de la contraseña no coincide.'
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizar datos
            $usuario->nombre = $request->nombre;
            $usuario->apellido = $request->apellido;
            $usuario->email = $request->email;
            $usuario->rol = $request->rol;

            if ($request->filled('password')) {
                $usuario->password = Hash::make($request->password);
            }

            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente.',
                'data' => $usuario
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage()
            ], 500);
        }
    }


    public function destroy(string $id)
    {
        try {
            $usuario = Usuario::find($id);
            
            if (!$usuario) {
                if (request()->expectsJson() || request()->ajax() || request()->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
                }
                return redirect()->route('usuarios.index')->with('error', 'Usuario no encontrado.');
            }
            
            $nombre = $usuario->nombre . ' ' . $usuario->apellido;
            $usuario->delete();
            
            if (request()->expectsJson() || request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => "Usuario {$nombre} eliminado exitosamente."
                ], 200);
            }
            
            return redirect()->route('usuarios.index')->with('success', "Usuario {$nombre} eliminado exitosamente.");
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar usuario: ' . $e->getMessage());
            
            if (request()->expectsJson() || request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Error al eliminar usuario: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('usuarios.index')->with('error', 'Error al eliminar usuario: ' . $e->getMessage());
        }
    }

    public function showLogin()
    {
        return view('usuarios.login');
    }

    public function login(Request $request)
    {
        // Validación de los datos
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:100',
            'password' => 'required|min:8'
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ser un correo electrónico válido.',
            'email.max' => 'El correo electrónico no puede tener más de 100 caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Buscar el usuario manualmente
        $usuario = Usuario::where('email', $request->email)->first();
        
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos'
            ], 401);
        }

        // Verificar la contraseña
        if (!Hash::check($request->password, $usuario->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos'
            ], 401);
        }

        // Iniciar sesión manualmente (ahora acepta usuarios normales y admin)
        Auth::login($usuario, $request->has('remember'));

        // Actualizar último acceso
        $usuario->update(['ultimo_acceso' => now()]);

        // Regenerar sesión por seguridad
        $request->session()->regenerate();

        // Redirigir según el rol
        // - Admin: ir a dashboard (o a la URL intended si venía de una ruta protegida)
        // - No admin: ir a home pública
        $isAdmin = $usuario->rol === 'admin';

        $intended = $request->session()->pull('url.intended');
        $redirectTo = null;

        if ($isAdmin) {
            $redirectTo = $intended ?: route('dashboard');
        } else {
            $redirectTo = route('clientes.home');
        }

        // Si el frontend manda redirect_to, úsalo solo si corresponde al rol y es una URL interna
        $requestedRedirect = $request->get('redirect_to');
        if (is_string($requestedRedirect) && $requestedRedirect !== '') {
            $isInternal = str_starts_with($requestedRedirect, '/') && !str_starts_with($requestedRedirect, '//');
            if ($isInternal) {
                if ($isAdmin) {
                    $redirectTo = $requestedRedirect;
                } else {
                    // Usuarios no-admin no deben ser enviados a rutas admin
                    $redirectTo = $requestedRedirect === '/dashboard' ? route('clientes.home') : $requestedRedirect;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'redirect' => $redirectTo
        ]);
    }

    /**
     * Redirigir a Google para autenticación OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Manejar callback de Google OAuth
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Buscar usuario existente por email o provider_id
            $usuario = Usuario::where('email', $googleUser->email)
                ->orWhere(function($query) use ($googleUser) {
                    $query->where('provider', 'google')
                          ->where('provider_id', $googleUser->id);
                })
                ->first();

            if ($usuario) {
                // Actualizar información si es necesario
                $usuario->update([
                    'provider' => 'google',
                    'provider_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'ultimo_acceso' => now()
                ]);
            } else {
                // Crear nuevo usuario
                $nombreCompleto = explode(' ', $googleUser->name, 2);
                $usuario = Usuario::create([
                    'nombre' => $nombreCompleto[0] ?? $googleUser->name,
                    'apellido' => $nombreCompleto[1] ?? '',
                    'email' => $googleUser->email,
                    'provider' => 'google',
                    'provider_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'rol' => 'cliente', // Por defecto cliente
                    'ultimo_acceso' => now()
                ]);
            }

            Auth::login($usuario);
            $request->session()->regenerate();

            return redirect()->route('clientes.home')->with('status', 'Inicio de sesión exitoso con Google');

        } catch (\Exception $e) {
            Log::error('Error en Google OAuth: ' . $e->getMessage());
            return redirect()->route('clientes.home')->with('error', 'Error al iniciar sesión con Google');
        }
    }

    /**
     * Redirigir a Facebook para autenticación OAuth
     */
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    /**
     * Manejar callback de Facebook OAuth
     */
    public function handleFacebookCallback(Request $request)
    {
        try {
            $facebookUser = Socialite::driver('facebook')->user();

            // Buscar usuario existente por email o provider_id
            $usuario = Usuario::where('email', $facebookUser->email)
                ->orWhere(function($query) use ($facebookUser) {
                    $query->where('provider', 'facebook')
                          ->where('provider_id', $facebookUser->id);
                })
                ->first();

            if ($usuario) {
                // Actualizar información si es necesario
                $usuario->update([
                    'provider' => 'facebook',
                    'provider_id' => $facebookUser->id,
                    'avatar' => $facebookUser->avatar,
                    'ultimo_acceso' => now()
                ]);
            } else {
                // Crear nuevo usuario
                $nombreCompleto = explode(' ', $facebookUser->name, 2);
                $usuario = Usuario::create([
                    'nombre' => $nombreCompleto[0] ?? $facebookUser->name,
                    'apellido' => $nombreCompleto[1] ?? '',
                    'email' => $facebookUser->email ?? $facebookUser->id . '@facebook.com',
                    'provider' => 'facebook',
                    'provider_id' => $facebookUser->id,
                    'avatar' => $facebookUser->avatar,
                    'rol' => 'cliente', // Por defecto cliente
                    'ultimo_acceso' => now()
                ]);
            }

            Auth::login($usuario);
            $request->session()->regenerate();

            return redirect()->route('clientes.home')->with('status', 'Inicio de sesión exitoso con Facebook');

        } catch (\Exception $e) {
            Log::error('Error en Facebook OAuth: ' . $e->getMessage());
            return redirect()->route('clientes.home')->with('error', 'Error al iniciar sesión con Facebook');
        }
    }
}