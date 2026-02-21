@extends('suppliers')

@section('Titulo pagina', 'Editar Proveedor')

@section('aside')
    @include('partes.aside')
@endsection

@section('contenido')
    <div class="form-container">
        <!-- Header -->
        <div class="form-header">
            <h1>Editar Proveedor</h1>
            <p>Actualiza la información del proveedor</p>
        </div>

        <!-- Form card -->
        <div class="form-card">
            <!-- Feedback message -->
            <div id="mensajeFeedbackEditar" class="feedback-message hidden">
                <i class="fas fa-info-circle"></i>
                <span></span>
            </div>

            <form id="formEdicion" class="form-body" 
                  action="{{ route('suppliers.update', $supplier->supplier_id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Supplier name -->
                <div class="form-group">
                    <label for="name">Nombre del Proveedor</label>
                    <input type="text" id="name" name="name" required 
                           value="{{ old('name', $supplier->name) }}"
                           placeholder="Ej: Distribuidora ABC S.A.">
                </div>

                <!-- Primary contact -->
                <div class="form-group">
                    <label for="primary_contact">Contacto Principal</label>
                    <input type="text" id="primary_contact" name="primary_contact" required 
                           value="{{ old('primary_contact', $supplier->primary_contact) }}"
                           placeholder="Ej: María González">
                </div>

                <!-- Phone and email row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <input type="tel" id="phone" name="phone" required 
                               value="{{ old('phone', $supplier->phone) }}"
                               placeholder="Ej: 88887777">
                    </div>
                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" id="email" name="email" required 
                               value="{{ old('email', $supplier->email) }}"
                               placeholder="contacto@proveedor.com">
                    </div>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label for="address">Dirección</label>
                    <textarea id="address" name="address" required rows="2" 
                              placeholder="Ej: San José, 200m norte de la iglesia">{{ old('address', $supplier->address) }}</textarea>
                </div>

                <!-- Delivery time and rating row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="delivery_time">Tiempo de Entrega (días)</label>
                        <input type="number" id="delivery_time" name="delivery_time" required 
                               value="{{ old('delivery_time', $supplier->delivery_time) }}"
                               min="1" max="365" placeholder="Ej: 7">
                    </div>
                    <div class="form-group optional">
                        <label for="rating">Evaluación (0-5)</label>
                        <input type="number" id="rating" name="rating" 
                               value="{{ old('rating', $supplier->rating) }}"
                               min="0" max="5" step="0.1" placeholder="Ej: 4.5">
                    </div>
                </div>

                <!-- Additional info -->
                <div class="info-section">
                    <p>
                        <strong>Información importante</strong><br>
                    </p>
                    <p>
                        Todos los campos marcados con * son obligatorios. El tiempo de entrega debe ser entre 1 y 365 días.
                        La evaluación es opcional y debe estar entre 0 y 5. Una vez creado el proveedor, podrás editar su información desde la lista de proveedores.
                    </p>
                </div>

                <!-- Form actions -->
                <div class="form-actions">
                    <button type="submit" id="btnSubmit" class="btn btn-primary" 
                            onclick="editSupplier(event)">
                        <i class="fas fa-save"></i>
                        <span id="btnTexto">Guardar Cambios</span>
                        <span id="btnCargando" class="hidden">Guardando...</span>
                    </button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/suppliers/supplier.css') }}">
@endpush