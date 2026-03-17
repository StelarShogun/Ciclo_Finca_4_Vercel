@extends('usuarios')

@section('Titulo pagina', 'Iniciar Sesión')

@section('contenido')
    <div class="auth-container">
        <!-- FORMULARIO DE LOGIN -->
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
                <!-- Mensaje de feedback -->
                <div id="mensajeFeedbackLogin" class="feedback-message hidden">
                    <i class="fas fa-info-circle"></i>
                    <span id="mensajeLoginTexto"></span>
                </div>

                <form id="formLogin" class="formulario-body" action="{{ route('admin.login.submit') }}" method="POST">
                    @csrf

                    <!-- Email -->
                    <div class="form-group">
                        <label for="loginEmail">Correo Electrónico *</label>
                        <input type="email" id="loginEmail" name="gmail" required placeholder="ejemplo@correo.com" value="{{ old('gmail') }}">
                    </div>

                    <!-- Contraseña -->
                    <div class="form-group">
                        <label for="loginPassword">Contraseña *</label>
                        <div class="password-container">
                            <input type="password" id="loginPassword" name="password" required
                                placeholder="Ingresa tu contraseña">
                            <i class="fas fa-eye" id="togglePassword"></i>
                        </div>
                    </div>

                    <!-- Acciones del formulario -->
                    <div class="form-actions">
                        <button type="submit" id="btnLoginSubmit" class="btn btn-primary full-width">
                            <i class="fas fa-sign-in-alt"></i>
                            <span id="btnLoginTexto">Iniciar Sesión</span>
                        </button>
                    </div>
                </form>

                @if ($errors->any())
                    <span id="authError" data-message="{{ implode(' ', $errors->all()) }}" style="display:none"></span>
                @endif
            </div>
        </div>
    </div>

    @push('styles')
        @vite(['resources/css/admin.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/admin.js'])
    @endpush
@endsection
