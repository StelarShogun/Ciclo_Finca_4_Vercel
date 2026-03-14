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
                        <input type="password" id="loginPassword" name="password" required
                            placeholder="Ingresa tu contraseña">
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Acciones del formulario -->
                    <div class="form-actions">
                        <button type="submit" id="btnLoginSubmit" class="btn btn-primary full-width">
                            <i class="fas fa-sign-in-alt"></i>
                            <span id="btnLoginTexto">Iniciar Sesión</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
