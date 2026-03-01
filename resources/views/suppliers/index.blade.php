@extends('suppliers')

@section('Titulo pagina')
    Proveedores
@endsection

@section('aside')
    @include('partes.aside')
@endsection

@section('header')
    <header class="page-header">
        <div>
            <h1>Gestión de Proveedores</h1>
            <p>Administra los proveedores del sistema</p>
        </div>
        <div class="page-header-actions">
            <button class="btn btn-primary" id="open-new-supplier-modal">
                <i class="fas fa-plus"></i>
                Nuevo Proveedor
            </button>
        </div>
    </header>
@endsection

@section('contenido')
    <div class="suppliers-container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-title">Total Proveedores</p>
                        <p class="stat-value" id="totalProveedores">{{ $suppliers->total() }}</p>
                    </div>
                    <div class="stat-icon blue">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-title">Promedio Evaluación</p>
                        <p class="stat-value" id="promedioEvaluacion">
                            {{ number_format($averageRating, 2) }}
                        </p>
                    </div>
                    <div class="stat-icon green">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <div class="filters-container">
                <div class="search-input">
                    <input type="text" id="buscarNombre" placeholder="Buscar por nombre de proveedor..."
                        value="{{ request('name') }}">
                </div>

                <div class="search-input">
                    <input type="text" id="buscarContacto" placeholder="Buscar por contacto principal..."
                        value="{{ request('contact') }}">
                </div>

                <button type="button" class="button button-primary" id="btnBuscar">
                    <i class="fas fa-search"></i>
                    Buscar
                </button>

                @if (request('name') || request('contact'))
                    <button type="button" class="button button-secondary" id="limpiarFiltros">
                        <i class="fas fa-times"></i>
                        Limpiar Filtros
                    </button>
                @endif
            </div>
        </div>

        <!-- Suppliers Table -->
        <div class="suppliers-table-wrapper">
            <div class="table-responsive">
                <table class="suppliers-table">
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
                            <tr class="supplier-row">
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
                                        <form action="{{ route('suppliers.destroy', $supplier->supplier_id) }}"
                                            method="POST" onsubmit="return deleteSupplier(event)" class="inline">
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
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-truck"></i>
                                        <h3>No hay proveedores registrados</h3>
                                        <p>No se encontraron proveedores que coincidan con tu búsqueda.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <x-pagination :paginator="$suppliers" label="de proveedores" />
    </div>

    <!-- Modal: Detalles del Proveedor -->
    <div id="modalDetalleProveedor" class="edit-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> Detalles del Proveedor</h3>
                <button onclick="closeModal()" class="modal-close" id="close-proveedor-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="supplier-details">
                    <div class="detail-row">
                        <label>Nombre:</label>
                        <span id="modalProveedorNombre">-</span>
                    </div>
                    <div class="detail-row">
                        <label>Email:</label>
                        <span id="modalProveedorEmail">-</span>
                    </div>
                    <div class="detail-row">
                        <label>Teléfono:</label>
                        <span id="modalProveedorTelefono">-</span>
                    </div>
                    <div class="detail-row">
                        <label>Dirección:</label>
                        <span id="modalProveedorDireccion">-</span>
                    </div>
                    <div class="detail-row">
                        <label>Evaluación:</label>
                        <span id="modalProveedorEvaluacion">-</span>
                    </div>
                    <div class="detail-row">
                        <label>Estado:</label>
                        <span id="modalProveedorEstado">-</span>
                    </div>
                    <div class="detail-row">
                        <label>Fecha de Registro:</label>
                        <span id="modalProveedorFechaRegistro">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Nuevo Proveedor -->
    <div id="new-supplier-modal" class="edit-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Nuevo Proveedor</h3>
                <button class="modal-close" id="close-new-supplier-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="new-supplier-form">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-supplier-nombre">Nombre del Proveedor *</label>
                            <input type="text" id="new-supplier-nombre" name="name" class="form-input" required>
                            <div class="error-message" id="error-new-name"></div>
                        </div>
                        <div class="form-group">
                            <label for="new-supplier-contacto">Contacto Principal *</label>
                            <input type="text" id="new-supplier-contacto" name="primary_contact" class="form-input"
                                required>
                            <div class="error-message" id="error-new-primary_contact"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-supplier-telefono">Teléfono *</label>
                            <input type="tel" id="new-supplier-telefono" name="phone" class="form-input" required>
                            <div class="error-message" id="error-new-phone"></div>
                        </div>
                        <div class="form-group">
                            <label for="new-supplier-email">Correo Electrónico *</label>
                            <input type="email" id="new-supplier-email" name="email" class="form-input" required>
                            <div class="error-message" id="error-new-email"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new-supplier-direccion">Dirección *</label>
                        <textarea id="new-supplier-direccion" name="address" class="form-textarea" rows="3" required></textarea>
                        <div class="error-message" id="error-new-address"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-supplier-tiempo">Tiempo de Entrega (días) *</label>
                            <input type="number" id="new-supplier-tiempo" name="delivery_time" class="form-input"
                                min="1" max="365" required>
                            <div class="error-message" id="error-new-delivery_time"></div>
                        </div>
                        <div class="form-group">
                            <label for="new-supplier-evaluacion">Evaluación (0-5)</label>
                            <input type="number" id="new-supplier-evaluacion" name="rating" class="form-input"
                                min="0" max="5" step="0.1">
                            <div class="error-message" id="error-new-rating"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary" id="cancel-new-supplier">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="button button-primary" id="save-new-supplier">
                    <i class="fas fa-save"></i> Guardar Proveedor
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Proveedor -->
    <div id="edit-supplier-modal" class="edit-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Editar Proveedor</h3>
                <button class="modal-close" id="close-edit-supplier-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="edit-supplier-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit-supplier-id" name="supplier_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-supplier-nombre">Nombre del Proveedor *</label>
                            <input type="text" id="edit-supplier-nombre" name="name" class="form-input" required>
                            <div class="error-message" id="error-edit-name"></div>
                        </div>
                        <div class="form-group">
                            <label for="edit-supplier-contacto">Contacto Principal *</label>
                            <input type="text" id="edit-supplier-contacto" name="primary_contact" class="form-input"
                                required>
                            <div class="error-message" id="error-edit-primary_contact"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-supplier-telefono">Teléfono *</label>
                            <input type="tel" id="edit-supplier-telefono" name="phone" class="form-input"
                                required>
                            <div class="error-message" id="error-edit-phone"></div>
                        </div>
                        <div class="form-group">
                            <label for="edit-supplier-email">Correo Electrónico *</label>
                            <input type="email" id="edit-supplier-email" name="email" class="form-input" required>
                            <div class="error-message" id="error-edit-email"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit-supplier-direccion">Dirección *</label>
                        <textarea id="edit-supplier-direccion" name="address" class="form-textarea" rows="3" required></textarea>
                        <div class="error-message" id="error-edit-address"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-supplier-tiempo">Tiempo de Entrega (días) *</label>
                            <input type="number" id="edit-supplier-tiempo" name="delivery_time" class="form-input"
                                min="1" max="365" required>
                            <div class="error-message" id="error-edit-delivery_time"></div>
                        </div>
                        <div class="form-group">
                            <label for="edit-supplier-evaluacion">Evaluación (0-5)</label>
                            <input type="number" id="edit-supplier-evaluacion" name="rating" class="form-input"
                                min="0" max="5" step="0.1">
                            <div class="error-message" id="error-edit-rating"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary" id="cancel-edit-supplier">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="button button-primary" id="save-edit-supplier">
                    <i class="fas fa-save"></i> Actualizar Proveedor
                </button>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    @vite(['resources/css/suppliers/supplier-entry.css'])
@endpush
