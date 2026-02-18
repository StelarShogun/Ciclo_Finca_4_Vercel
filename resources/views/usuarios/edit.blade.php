@extends('usuarios')

@section('Titulo pagina', 'Editar Usuario')

@section('aside')
    @include('partes.aside')
@endsection

@section('contenido')
    <div class="formulario-container">
        <!-- Cabecera -->
        <div class="formulario-header">
            <h1>Editar Usuario</h1>
            <p>Actualiza la información del usuario</p>
        </div>

        <!-- Tarjeta del formulario -->
        <div class="formulario-card">
            <!-- Mensaje de feedback -->
            <div id="mensajeFeedbackEditar" class="feedback-message hidden">
                <i class="fas fa-info-circle"></i>
                <span id="mensajeTexto"></span>
            </div>

            <form id="formEdicion" class="formulario-body" action="{{ route('usuarios.update', $usuario->usuario_id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Fila de nombre y apellido -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required value="{{ $usuario->nombre }}" placeholder="Ej: Juan">
                    </div>
                    <div class="form-group">
                        <label for="apellido">Apellido *</label>
                        <input type="text" id="apellido" name="apellido" required value="{{ $usuario->apellido }}" placeholder="Ej: Pérez">
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Correo Electrónico *</label>
                    <input type="email" id="email" name="email" required value="{{ $usuario->email }}" placeholder="ejemplo@correo.com">
                </div>

                <!-- Rol -->
                <div class="form-group">
                    <label for="rol">Rol *</label>
                    <select id="rol" name="rol" required>
                        <option value="">Selecciona un rol</option>
                        <option value="cliente" {{ $usuario->rol == 'cliente' ? 'selected' : '' }}>Cliente</option>
                        <option value="vendedor" {{ $usuario->rol == 'vendedor' ? 'selected' : '' }}>Vendedor</option>
                        <option value="tecnico" {{ $usuario->rol == 'tecnico' ? 'selected' : '' }}>Técnico</option>
                        <option value="admin" {{ $usuario->rol == 'admin' ? 'selected' : '' }}>Administrador</option>
                    </select>
                </div>

                <!-- Sección de contraseña -->
                <div class="info-section">
                    <p>
                        <strong>Cambiar Contraseña (opcional)</strong><br>
                        Deja los campos vacíos si no deseas cambiar la contraseña
                    </p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Nueva Contraseña</label>
                            <input type="password" id="password" name="password" minlength="6" placeholder="Mínimo 6 caracteres">
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Confirmar Nueva Contraseña</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" minlength="6" placeholder="Repite la contraseña">
                        </div>
                    </div>
                </div>

                <!-- Acciones del formulario -->
                <div class="form-actions">
                    <button type="submit" id="btnSubmit" class="btn btn-primary" onclick="editarUsuario(event)">
                        <i class="fas fa-save"></i>
                        <span id="btnTexto">Actualizar Usuario</span>
                        <span id="btnCargando" class="hidden">Actualizando...</span>
                    </button>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('estilos.php') }}">
@endpush
