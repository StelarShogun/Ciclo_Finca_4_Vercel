@extends('proveedores')

@section('Titulo pagina', 'Registrar Proveedor')

@section('aside')
    @include('partes.aside')
@endsection

@section('contenido')
    <div class="formulario-container">
        <!-- Cabecera -->
        <div class="formulario-header">
            <h1>Registrar Nuevo Proveedor</h1>
            <p>Completa el formulario para agregar un proveedor</p>
        </div>

        <!-- Tarjeta del formulario -->
        <div class="formulario-card">
            <!-- Mensaje de feedback -->
            <div id="mensajeFeedbackRegistro" class="feedback-message hidden">
                <i class="fas fa-info-circle"></i>
                <span></span>
            </div>

            <form id="formRegistro" class="formulario-body" action="{{ route('proveedores.store') }}" method="POST">
                @csrf

                <!-- Nombre del proveedor -->
                <div class="form-group">
                    <label for="nombre">Nombre del Proveedor</label>
                    <input type="text" id="nombre" name="nombre" required 
                           placeholder="Ej: Distribuidora ABC S.A.">
                </div>

                <!-- Contacto principal -->
                <div class="form-group">
                    <label for="contacto_principal">Contacto Principal</label>
                    <input type="text" id="contacto_principal" name="contacto_principal" required 
                           placeholder="Ej: María González">
                </div>

                <!-- Fila de teléfono y email -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" required 
                               placeholder="Ej: 88887777">
                    </div>
                    <div class="form-group">
                        <label for="correo_electronico">Correo Electrónico</label>
                        <input type="email" id="correo_electronico" name="correo_electronico" required 
                               placeholder="contacto@proveedor.com">
                    </div>
                </div>

                <!-- Dirección -->
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <textarea id="direccion" name="direccion" required rows="2" 
                              placeholder="Ej: San José, 200m norte de la iglesia"></textarea>
                </div>

                <!-- Fila de tiempo de entrega y evaluación -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="tiempo_entrega">Tiempo de Entrega (días)</label>
                        <input type="number" id="tiempo_entrega" name="tiempo_entrega" required 
                               min="1" max="365" placeholder="Ej: 7">
                    </div>
                    <div class="form-group optional">
                        <label for="evaluacion">Evaluación (0-5)</label>
                        <input type="number" id="evaluacion" name="evaluacion" 
                               min="0" max="5" step="0.1" placeholder="Ej: 4.5">
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="info-section">
                    <p>
                        <strong>Información importante</strong><br>
                        Todos los campos marcados con * son obligatorios. El tiempo de entrega debe ser entre 1 y 365 días.
                        La evaluación es opcional y debe estar entre 0 y 5. Una vez creado el proveedor, podrás editar su información desde la lista de proveedores.
                    </p>
                </div>

                <!-- Acciones del formulario -->
                <div class="form-actions">
                    <button type="submit" id="btnRegistroSubmit" class="btn btn-primary" 
                            onclick="registrarProveedor(event)">
                        <i class="fas fa-truck"></i>
                        <span id="btnRegistroTexto">Registrar Proveedor</span>
                        <span id="btnRegistroCargando" class="hidden">Registrando...</span>
                    </button>
                    <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Volver al Listado
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/proveedores/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/proveedores/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/proveedores/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/proveedores/formularios.css') }}">
@endpush