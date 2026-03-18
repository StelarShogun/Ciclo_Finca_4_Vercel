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

                <form id="formLogin" class="formulario-body" action="{{ route('login') }}" method="POST">
                    @csrf

                    <!-- Email -->
                    <div class="form-group">
                        <label for="loginEmail">Correo Electrónico *</label>
                        <input type="email" id="loginEmail" name="email" required placeholder="ejemplo@correo.com">
                    </div>

                    <!-- Contraseña -->
                    <div class="form-group">
                        <label for="loginPassword">Contraseña *</label>
                        <input type="password" id="loginPassword" name="password" required
                            placeholder="Ingresa tu contraseña">
                    </div>

                    <!-- Acciones del formulario -->
                    <div class="form-actions">
                        <button type="button" id="btnLoginSubmit" class="btn btn-primary full-width"
                            onclick="loginUsuario(event)">
                            <i class="fas fa-sign-in-alt"></i>
                            <span id="btnLoginTexto">Iniciar Sesión</span>
                            <span id="btnLoginCargando" class="hidden">Iniciando...</span>
                        </button>
                    </div>
                </form>

                <!-- Enlace para registro de clientes -->
                <div class="auth-switch">
                    <p>¿No tienes una cuenta?</p>
                    <a href="{{ route('clients.register.form') }}" class="auth-link">Regístrate aquí (crear cuenta cliente)</a>
                </div>
            </div>
        </div>

        <!-- Enlace a registro de clientes -->
        <div id="registroForm" class="auth-form">
            <div class="formulario-header">
                <h1>Registrar como cliente</h1>
                <p>Usa la página de registro de clientes para crear tu cuenta.</p>
            </div>
            <div class="formulario-card">
                <a href="{{ route('clients.register.form') }}" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Ir a crear cuenta (cliente)
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/usuarios.js'])
@endpush
