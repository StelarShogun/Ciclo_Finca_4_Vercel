@extends('usuarios')

@section('Titulo pagina', 'Registrar Usuario')

@section('aside')
    @include('partes.aside')
@endsection

@section('contenido')
    <div class="formulario-container">
        <!-- Cabecera -->
        <div class="formulario-header">
            <h1>Registrar Nuevo Usuario</h1>
            <p>Completa el formulario para crear una cuenta</p>
        </div>

        <!-- Tarjeta del formulario -->
        <div class="formulario-card">
            <!-- Mensaje de feedback -->
            <div id="mensajeFeedbackRegistro" class="feedback-message hidden">
                <i class="fas fa-info-circle"></i>
                <span id="mensajeTexto"></span>
            </div>

            <form id="formRegistro" class="formulario-body" action="{{ route('usuarios.store') }}" method="POST">
                @csrf

                <!-- Fila de nombre y apellido -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required placeholder="Ej: Juan">
                    </div>
                    <div class="form-group">
                        <label for="apellido">Apellido *</label>
                        <input type="text" id="apellido" name="apellido" required placeholder="Ej: Pérez">
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Correo Electrónico *</label>
                    <input type="email" id="email" name="email" required placeholder="ejemplo@correo.com">
                </div>

                <!-- Rol -->
                <div class="form-group">
                    <label for="rol">Rol *</label>
                    <select id="rol" name="rol" required>
                        <option value="">Selecciona un rol</option>
                        <option value="cliente">Cliente</option>
                        <option value="vendedor">Vendedor</option>
                        <option value="tecnico">Técnico</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <!-- Contraseñas -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Contraseña *</label>
                        <input type="password" id="password" name="password" required minlength="6" 
                               placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirmar Contraseña *</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required 
                               minlength="6" placeholder="Repite la contraseña">
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="info-section">
                    <p>
                        <strong>Información importante</strong><br>
                        Todos los campos marcados con * son obligatorios. La contraseña debe tener al menos 6 caracteres.
                        Una vez creado el usuario, podrás editar su información desde la lista de usuarios.
                    </p>
                </div>

                <!-- Acciones del formulario -->
                <div class="form-actions">
                    <button type="submit" id="btnRegistroSubmit" class="btn btn-primary" onclick="registrarUsuario(event, 'registro')">
                        <i class="fas fa-user-plus"></i>
                        <span id="btnRegistroTexto">Registrar Usuario</span>
                        <span id="btnRegistroCargando" class="hidden">Registrando...</span>
                    </button>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Volver al Listado
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('estilos.php') }}">
@endpush

