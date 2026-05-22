<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar Sesión - Ciclo Finca 4</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/login/login.css'])
</head>
<body>

    <div class="auth-container">

        {{-- Login form --}}
        <div id="loginForm" class="auth-form active">

            <div class="formulario-header">
                <h1>Iniciar Sesión - Administradores</h1>
                <p>Solo los administradores pueden acceder al sistema</p>
                <div class="alert alert-info">
                    <i class="fas fa-lock"></i>
                    <strong>Acceso Restringido:</strong> Este sistema está disponible únicamente para usuarios con rol de administrador.
                </div>
            </div>

            <div class="formulario-card">

                {{-- JS-controlled feedback message container --}}
                <div id="mensajeFeedbackLogin" class="feedback-message hidden">
                    <i class="fas fa-info-circle"></i>
                    <span id="mensajeLoginTexto"></span>
                </div>

                <form id="formLogin" class="formulario-body" action="{{ route('admin.login.submit') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="loginEmail">Correo Electrónico *</label>
                        <input type="email" id="loginEmail" name="gmail" required
                            placeholder="ejemplo@correo.com" value="{{ old('gmail') }}">
                    </div>

                    <div class="form-group">
                        <label for="loginPassword">Contraseña *</label>
                        <div class="password-container">
                            <input type="password" id="loginPassword" name="password" required
                                placeholder="Ingresa tu contraseña">
                            {{-- Toggle password visibility (handled in login.js) --}}
                            <i class="fas fa-eye" id="togglePassword"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.key') }}"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" id="btnLoginSubmit" class="btn btn-primary full-width">
                            <i class="fas fa-sign-in-alt"></i>
                            <span id="btnLoginTexto">Iniciar Sesión</span>
                        </button>
                    </div>
                </form>

                {{-- Hidden element used by JS to display validation errors via SweetAlert2 --}}
                @if ($errors->any())
                    <span id="authError" data-message="{{ implode(' ', $errors->all()) }}" style="display:none"></span>
                @endif

            </div>
        </div>
    </div>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @vite(['resources/js/admin/shell.js', 'resources/js/admin/login/login.js'])

</body>
</html>