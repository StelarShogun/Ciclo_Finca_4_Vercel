@extends('admin.layouts.suppliers')

@section('Titulo pagina', 'Proveedores - Ciclo Finca 4 Admin')

@push('styles')
    @vite(['resources/css/admin/suppliers/suppliers.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    <div class="sales-container">

        {{-- ==================== HEADER ==================== --}}
        @component('admin.partials.page-header', [
            'title' => 'Gestión de proveedores',
            'description' =>
                'Administra la información de proveedores, contactos, tiempos de entrega y evaluaciones del sistema.',
        ])
            @slot('actions')
                <button class="btn btn-primary" id="open-new-supplier-modal">
                    <i class="fas fa-plus"></i> Nuevo proveedor
                </button>
            @endslot
        @endcomponent

        @if (session('status'))
            <x-admin-alert type="success" :message="session('status')" dismissible />
        @endif
        @if (session('error'))
            <x-admin-alert type="error" :message="session('error')" />
        @endif

        {{-- ==================== KPI CARDS ==================== --}}
        <div class="kpi-grid">

            {{-- Total registered suppliers --}}
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3 class="kpi-title">Total Proveedores</h3>
                    <div class="kpi-icon info"><i class="fas fa-truck"></i></div>
                </div>
                <p class="kpi-value" id="totalProveedores">{{ $suppliers->total() }}</p>
            </div>

            {{-- Average supplier rating --}}
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3 class="kpi-title">Promedio Evaluación</h3>
                    <div class="kpi-icon success"><i class="fas fa-star"></i></div>
                </div>
                <p class="kpi-value" id="promedioEvaluacion">{{ number_format($averageRating, 2) }}</p>
            </div>

        </div>

        {{-- ==================== FILTERS ==================== --}}
        <div class="filters-section">
            <h2 class="filters-title">Filtros de Búsqueda</h2>
            <div class="filters-grid">

                <div class="filter-group">
                    <label for="buscarNombre">Nombre del Proveedor</label>
                    <input type="text" id="buscarNombre" placeholder="Buscar por nombre..."
                        value="{{ request('name') }}">
                </div>

                <div class="filter-group">
                    <label for="buscarContacto">Contacto Principal</label>
                    <input type="text" id="buscarContacto" placeholder="Buscar por contacto..."
                        value="{{ request('contact') }}">
                </div>

                <div class="filter-group filter-buttons">
                    <button type="button" class="btn btn-primary filter-btn" id="btnBuscar">
                        <i class="fas fa-search"></i> Buscar
                    </button>

                    {{-- Clear filters button: only shown when filters are active --}}
                    @if (request('name') || request('contact'))
                        <button type="button" class="btn btn-secondary filter-btn" id="limpiarFiltros">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                    @endif
                </div>

            </div>
        </div>

        {{-- ==================== SUPPLIERS TABLE ==================== --}}
        <div data-cf4-ajax-pagination data-cf4-ajax-scroll>
        <div id="cf4-list-fragment">
        <div class="sales-table-container">
            <table class="sales-table">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Correo Electrónico</th>
                        <th>Dirección</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody id="tablaProveedores">
                    @forelse($suppliers as $supplier)
                        <tr>
                            {{-- Avatar uses first letter of supplier name --}}
                            <td>
                                <div class="provider-info">
                                    <div class="provider-avatar">
                                        {{ substr($supplier->name, 0, 1) }}
                                    </div>
                                    <div class="provider-details">
                                        <h4 class="supplier-name">{{ $supplier->name }}</h4>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $supplier->primary_contact }}</td>
                            <td>{{ $supplier->phone }}</td>
                            <td>{{ $supplier->email }}</td>
                            <td>{{ $supplier->address }}</td>

                            {{-- Row actions: view, edit, delete --}}
                            <td>
                                <div class="actions-container">
                                    <button onclick="viewSupplierDetail('{{ $supplier->supplier_id }}')"
                                        class="action-btn view" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <button onclick="loadSupplierForEdit({{ $supplier->supplier_id }})"
                                        class="action-btn edit" title="Editar proveedor">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <form action="{{ route('suppliers.destroy', $supplier->supplier_id) }}" method="POST"
                                        class="inline js-supplier-delete-form" data-supplier-name="{{ $supplier->name }}">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="action-btn delete" title="Eliminar proveedor">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                <div style="padding: 40px; color: var(--color-muted);">
                                    <i class="fas fa-truck" style="font-size: 3rem; margin-bottom: 15px;"></i>
                                    <p>No hay proveedores registrados</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination component --}}
        <x-admin.pagination :paginator="$suppliers" label="proveedores" />
        </div>
        </div>

    {{-- ==================== MODAL: SUPPLIER DETAIL ==================== --}}
    <div id="modalDetalleProveedor" class="edit-modal">
        <div class="modal-backdrop" onclick="closeModal()"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> Detalles del Proveedor</h3>
                <button type="button" onclick="closeModal()" class="modal-close" id="close-proveedor-modal" aria-label="Cerrar"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body">
                <div class="supplier-detail-view">
                    <div class="detail-section">
                        <h4><i class="fas fa-building"></i> Información general</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Nombre</label>
                                <span id="modalProveedorNombre">-</span>
                            </div>

                            <div class="detail-item">
                                <label>Correo electrónico</label>
                                <span id="modalProveedorEmail">-</span>
                            </div>

                            <div class="detail-item">
                                <label>Teléfono</label>
                                <span id="modalProveedorTelefono">-</span>
                            </div>

                            <div class="detail-item">
                                <label>Dirección</label>
                                <span id="modalProveedorDireccion">-</span>
                            </div>

                            <div class="detail-item">
                                <label>Evaluación</label>
                                <span id="modalProveedorEvaluacion">-</span>
                            </div>

                            <div class="detail-item">
                                <label>Estado</label>
                                <span id="modalProveedorEstado">-</span>
                            </div>

                            <div class="detail-item">
                                <label>Fecha de registro</label>
                                <span id="modalProveedorFechaRegistro">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>

    {{-- ==================== MODAL: NEW SUPPLIER ==================== --}}
    <div id="new-supplier-modal" class="modal-overlay">
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Nuevo proveedor</h3>
                <button class="modal-close" id="close-new-supplier-modal">&times;</button>
            </div>

            <div class="modal-body">
                <form id="new-supplier-form">
                    @csrf

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-supplier-nombre">Nombre del Proveedor</label>
                            <input type="text" id="new-supplier-nombre" name="name" required>
                            <div class="error-message" id="error-new-name"></div>
                        </div>

                        <div class="form-group">
                            <label for="new-supplier-contacto">Contacto Principal</label>
                            <input type="text" id="new-supplier-contacto" name="primary_contact" required>
                            <div class="error-message" id="error-new-primary_contact"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-supplier-telefono">Teléfono</label>
                            <input type="tel" id="new-supplier-telefono" name="phone" required>
                            <div class="error-message" id="error-new-phone"></div>
                        </div>

                        <div class="form-group">
                            <label for="new-supplier-email">Correo Electrónico</label>
                            <input type="email" id="new-supplier-email" name="email" required>
                            <div class="error-message" id="error-new-email"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new-supplier-direccion">Dirección</label>
                        <textarea id="new-supplier-direccion" name="address" rows="3" required></textarea>
                        <div class="error-message" id="error-new-address"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-supplier-tiempo">Tiempo de entrega referencial (días) *</label>
                            <input type="number" id="new-supplier-tiempo" name="delivery_time" min="1"
                                max="365" required>
                            <div class="error-message" id="error-new-delivery_time"></div>
                        </div>

                        <div class="form-group">
                            <label for="new-supplier-evaluacion">Evaluación (0-5)</label>
                            <input type="number" id="new-supplier-evaluacion" name="rating" min="0"
                                max="5" step="0.1">
                            <div class="error-message" id="error-new-rating"></div>
                        </div>
                    </div>

                    <div style="display:flex; gap:15px; justify-content:flex-end; margin-top:20px;">
                        <button type="button" class="btn btn-secondary" id="cancel-new-supplier">
                            <i class="fas fa-times"></i> Cancelar
                        </button>

                        <button type="button" class="btn btn-primary" id="save-new-supplier">
                            <i class="fas fa-save"></i> Guardar Proveedor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ==================== MODAL: EDIT SUPPLIER ==================== --}}
    <div id="edit-supplier-modal" class="modal-overlay">
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Editar Proveedor</h3>
                <button class="modal-close" id="close-edit-supplier-modal">&times;</button>
            </div>

            <div class="modal-body">
                <form id="edit-supplier-form">
                    @csrf
                    @method('PUT')

                    {{-- Hidden field carries the supplier ID for the PUT request --}}
                    <input type="hidden" id="edit-supplier-id" name="supplier_id">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-supplier-nombre">Nombre del Proveedor *</label>
                            <input type="text" id="edit-supplier-nombre" name="name" required>
                            <div class="error-message" id="error-edit-name"></div>
                        </div>

                        <div class="form-group">
                            <label for="edit-supplier-contacto">Contacto Principal *</label>
                            <input type="text" id="edit-supplier-contacto" name="primary_contact" required>
                            <div class="error-message" id="error-edit-primary_contact"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-supplier-telefono">Teléfono *</label>
                            <input type="tel" id="edit-supplier-telefono" name="phone" required>
                            <div class="error-message" id="error-edit-phone"></div>
                        </div>

                        <div class="form-group">
                            <label for="edit-supplier-email">Correo Electrónico *</label>
                            <input type="email" id="edit-supplier-email" name="email" required>
                            <div class="error-message" id="error-edit-email"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit-supplier-direccion">Dirección *</label>
                        <textarea id="edit-supplier-direccion" name="address" rows="3" required></textarea>
                        <div class="error-message" id="error-edit-address"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-supplier-tiempo">Tiempo de Entrega (días) *</label>
                            <input type="number" id="edit-supplier-tiempo" name="delivery_time" min="1"
                                max="365" required>
                            <div class="error-message" id="error-edit-delivery_time"></div>
                        </div>

                        <div class="form-group">
                            <label for="edit-supplier-evaluacion">Evaluación (0-5)</label>
                            <input type="number" id="edit-supplier-evaluacion" name="rating" min="0"
                                max="5" step="0.1">
                            <div class="error-message" id="error-edit-rating"></div>
                        </div>
                    </div>

                    <div style="display:flex; gap:15px; justify-content:flex-end; margin-top:20px;">
                        <button type="button" class="btn btn-secondary" id="cancel-edit-supplier">
                            <i class="fas fa-times"></i> Cancelar
                        </button>

                        <button type="button" class="btn btn-primary" id="save-edit-supplier">
                            <i class="fas fa-save"></i> Actualizar Proveedor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
