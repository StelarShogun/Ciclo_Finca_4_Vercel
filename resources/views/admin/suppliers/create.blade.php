@extends('suppliers')

@section('Titulo pagina', 'Registrar Proveedor')

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    <div class="form-container">
        <!-- Header -->
        <div class="form-header">
            <h1>Registrar Nuevo Proveedor</h1>
            <p>Completa el formulario para agregar un proveedor</p>
        </div>

        <!-- Form card -->
        <div class="form-card">
            <!-- Feedback message -->
            <div id="mensajeFeedbackRegistro" class="feedback-message hidden">
                <i class="fas fa-info-circle"></i>
                <span></span>
            </div>

            <form id="formRegistro" class="form-body" action="{{ route('suppliers.store') }}" method="POST">
                @csrf

                <!-- Supplier name -->
                <div class="form-group">
                    <label for="name">Nombre del Proveedor</label>
                    <input type="text" id="name" name="name" required placeholder="Ej: Distribuidora ABC S.A.">
                </div>

                <!-- Primary contact -->
                <div class="form-group">
                    <label for="primary_contact">Contacto Principal</label>
                    <input type="text" id="primary_contact" name="primary_contact" required
                        placeholder="Ej: María González">
                </div>

                <!-- Phone and email row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <input type="tel" id="phone" name="phone" required placeholder="Ej: 88887777">
                    </div>
                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" id="email" name="email" required placeholder="contacto@proveedor.com">
                    </div>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label for="address">Dirección</label>
                    <textarea id="address" name="address" required rows="2" placeholder="Ej: San José, 200m norte de la iglesia"></textarea>
                </div>

                <!-- Delivery time and rating row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="delivery_time">Tiempo de Entrega (días)</label>
                        <input type="number" id="delivery_time" name="delivery_time" required min="1" max="365"
                            placeholder="Ej: 7">
                    </div>
                    <div class="form-group optional">
                        <label for="rating">Evaluación (0-5)</label>
                        <input type="number" id="rating" name="rating" min="0" max="5" step="0.1"
                            placeholder="Ej: 4.5">
                    </div>
                </div>

                <!-- Additional info -->
                <div class="info-section">
                    <p>
                        <strong>Información importante</strong><br>
                        Todos los campos marcados con * son obligatorios. El tiempo de entrega debe ser entre 1 y 365 días.
                        La evaluación es opcional y debe estar entre 0 y 5. Una vez creado el proveedor, podrás editar su
                        información desde la lista de proveedores.
                    </p>
                </div>

                <!-- Form actions -->
                <div class="form-actions">
                    <button type="submit" id="btnRegistroSubmit" class="btn btn-primary" onclick="registerSupplier(event)">
                        <i class="fas fa-truck"></i>
                        <span id="btnRegistroTexto">Registrar Proveedor</span>
                        <span id="btnRegistroCargando" class="hidden">Registrando...</span>
                    </button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Volver al Listado
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    @vite(['resources/css/suppliers/suppliers.css'])
@endpush
