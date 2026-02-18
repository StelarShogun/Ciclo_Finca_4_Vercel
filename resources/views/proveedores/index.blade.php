@extends('proveedores')

@section('Titulo pagina')
Proveedores
@endsection

@section('aside')
    @include('partes.aside')
@endsection

@section('header')
    <header class="usuarios-header">
        <div>
            <h1>Gestión de Proveedores</h1>
            <p>Administra los proveedores del sistema</p>
        </div>
        <div class="usuarios-actions">
            <button class="btn btn-primary" id="open-new-supplier-modal">
                <i class="fas fa-plus"></i>
                Nuevo Proveedor
            </button>
        </div>
    </header>
@endsection

@section('contenido')
    <div class="proveedores-container">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-title">Total Proveedores</p>
                        <p class="stat-value" id="totalProveedores">{{ $proveedores->total() }}</p>
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
                            {{ number_format($promedioEvaluacion, 2) }}
                        </p>
                    </div>
                    <div class="stat-icon green">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <div class="filters-container">
                <div class="search-input">
                    <input type="text" id="buscarNombre" placeholder="Buscar por nombre de proveedor..."
                        value="{{ request('nombre') }}">
                </div>

                <div class="search-input">
                    <input type="text" id="buscarContacto" placeholder="Buscar por contacto principal..."
                        value="{{ request('contacto') }}">
                </div>

                <button type="button" class="button button-primary" id="btnBuscar">
                    <i class="fas fa-search"></i>
                    Buscar
                </button>

                @if (request('nombre') || request('contacto'))
                    <button type="button" class="button button-secondary" id="limpiarFiltros">
                        <i class="fas fa-times"></i>
                        Limpiar Filtros
                    </button>
                @endif
            </div>
        </div>

        <!-- Tabla de Proveedores -->
        <div class="proveedores-table-container">
            <div class="table-responsive">
                <table class="proveedores-table">
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
                        @forelse($proveedores as $proveedor)
                            <tr class="proveedor-row">
                                <td>
                                    <div class="provider-info">
                                        <div class="provider-avatar">
                                            {{ substr($proveedor->nombre, 0, 1) }}
                                        </div>
                                        <div class="provider-details">
                                            <h4 class="proveedor-nombre">{{ $proveedor->nombre }}</h4>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $proveedor->contacto_principal }}</td>
                                <td>{{ $proveedor->telefono }}</td>
                                <td>{{ $proveedor->correo_electronico }}</td>
                                <td>{{ $proveedor->direccion }}</td>
                                <td>
                                    <div class="actions-container">
                                        <button onclick="verDetalle('{{ $proveedor->proveedor_id }}')" class="action-btn view" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="editarProveedor({{ $proveedor->proveedor_id }})"
                                            class="action-btn edit" title="Editar proveedor">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('proveedores.destroy', $proveedor->proveedor_id) }}"
                                            method="POST" onsubmit="return eliminarProveedor(event)" class="inline">
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

        <!-- Paginación -->
        <x-pagination :paginator="$proveedores" label="de proveedores" />
    </div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/proveedores.js') }}"></script>
    
    <script>
        // Las funciones están definidas en proveedores.js
        
        if (typeof eliminarProveedor === 'undefined') {
            function eliminarProveedor(event) {
                event.preventDefault();
                
                const form = event.target.closest('form');
                const url = form.action;
                const csrfToken = form.querySelector('input[name="_token"]').value;
                
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: 'Esta acción no se puede deshacer',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Eliminado',
                                    text: 'El proveedor ha sido eliminado correctamente',
                                    icon: 'success',
                                    confirmButtonText: 'Entendido'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: data.message || 'Error al eliminar el proveedor',
                                    icon: 'error',
                                    confirmButtonText: 'Entendido'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Error',
                                text: 'Error de conexión',
                                icon: 'error',
                                confirmButtonText: 'Entendido'
                            });
                        });
                    }
                });
            }
        }
        
        // Función para limpiar filtros
        document.addEventListener('DOMContentLoaded', function() {
            const limpiarBtn = document.getElementById('limpiarFiltros');
            if (limpiarBtn) {
                limpiarBtn.addEventListener('click', function() {
                    document.getElementById('buscar').value = '';
                    window.location.href = '{{ route("proveedores.index") }}';
                });
            }
        });
    </script>
@endpush

<!-- Modal para ver detalles del proveedor -->
<div id="modalDetalleProveedor" class="edit-modal">
    <div class="modal-backdrop"></div>
    <div class="modal-content modal-auto-size">
        <div class="modal-header">
            <h3><i class="fas fa-eye"></i> Detalles del Proveedor</h3>
            <button onclick="cerrarModal()" class="modal-close" id="close-proveedor-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="proveedor-details">
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
                <div class="modal-body">
                    <div style="display: grid; gap: 1rem;">
                        <div>
                            <strong>Nombre:</strong>
                            <p id="modalProveedorNombre" style="margin: 0.5rem 0 0 0; color: #666;">-</p>
                        </div>
                        <div>
                            <strong>Email:</strong>
                            <p id="modalProveedorEmail" style="margin: 0.5rem 0 0 0; color: #666;">-</p>
                        </div>
                        <div>
                            <strong>Teléfono:</strong>
                            <p id="modalProveedorTelefono" style="margin: 0.5rem 0 0 0; color: #666;">-</p>
                        </div>
                        <div>
                            <strong>Dirección:</strong>
                            <p id="modalProveedorDireccion" style="margin: 0.5rem 0 0 0; color: #666;">-</p>
                        </div>
                        <div>
                            <strong>Evaluación:</strong>
                            <p id="modalProveedorEvaluacion" style="margin: 0.5rem 0 0 0; color: #666;">-</p>
                        </div>
                        <div>
                            <strong>Estado:</strong>
                            <p id="modalProveedorEstado" style="margin: 0.5rem 0 0 0; color: #666;">-</p>
                        </div>
                        <div>
                            <strong>Fecha de Registro:</strong>
                            <p id="modalProveedorFechaRegistro" style="margin: 0.5rem 0 0 0; color: #666;">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="button button-secondary" onclick="cerrarModal()">
                <i class="fas fa-times"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Modal para crear nuevo proveedor -->
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
                        <input type="text" id="new-supplier-nombre" name="nombre" class="form-input" required>
                        <div class="error-message" id="error-new-nombre"></div>
                    </div>
                    <div class="form-group">
                        <label for="new-supplier-contacto">Contacto Principal *</label>
                        <input type="text" id="new-supplier-contacto" name="contacto_principal" class="form-input" required>
                        <div class="error-message" id="error-new-contacto"></div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="new-supplier-telefono">Teléfono *</label>
                        <input type="tel" id="new-supplier-telefono" name="telefono" class="form-input" required>
                        <div class="error-message" id="error-new-telefono"></div>
                    </div>
                    <div class="form-group">
                        <label for="new-supplier-email">Correo Electrónico *</label>
                        <input type="email" id="new-supplier-email" name="email" class="form-input" required>
                        <div class="error-message" id="error-new-email"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="new-supplier-direccion">Dirección *</label>
                    <textarea id="new-supplier-direccion" name="direccion" class="form-textarea" rows="3" required></textarea>
                    <div class="error-message" id="error-new-direccion"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="new-supplier-tiempo">Tiempo de Entrega (días) *</label>
                        <input type="number" id="new-supplier-tiempo" name="tiempo_entrega" class="form-input" min="1" max="365" required>
                        <div class="error-message" id="error-new-tiempo"></div>
                    </div>
                    <div class="form-group">
                        <label for="new-supplier-evaluacion">Evaluación (0-5)</label>
                        <input type="number" id="new-supplier-evaluacion" name="evaluacion" class="form-input" min="0" max="5" step="0.1">
                        <div class="error-message" id="error-new-evaluacion"></div>
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

<!-- Modal para editar proveedor -->
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
                <input type="hidden" id="edit-supplier-id" name="proveedor_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-supplier-nombre">Nombre del Proveedor *</label>
                        <input type="text" id="edit-supplier-nombre" name="nombre" class="form-input" required>
                        <div class="error-message" id="error-edit-nombre"></div>
                    </div>
                    <div class="form-group">
                        <label for="edit-supplier-contacto">Contacto Principal *</label>
                        <input type="text" id="edit-supplier-contacto" name="contacto_principal" class="form-input" required>
                        <div class="error-message" id="error-edit-contacto"></div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-supplier-telefono">Teléfono *</label>
                        <input type="tel" id="edit-supplier-telefono" name="telefono" class="form-input" required>
                        <div class="error-message" id="error-edit-telefono"></div>
                    </div>
                    <div class="form-group">
                        <label for="edit-supplier-email">Correo Electrónico *</label>
                        <input type="email" id="edit-supplier-email" name="email" class="form-input" required>
                        <div class="error-message" id="error-edit-email"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit-supplier-direccion">Dirección *</label>
                    <textarea id="edit-supplier-direccion" name="direccion" class="form-textarea" rows="3" required></textarea>
                    <div class="error-message" id="error-edit-direccion"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-supplier-tiempo">Tiempo de Entrega (días) *</label>
                        <input type="number" id="edit-supplier-tiempo" name="tiempo_entrega" class="form-input" min="1" max="365" required>
                        <div class="error-message" id="error-edit-tiempo"></div>
                    </div>
                    <div class="form-group">
                        <label for="edit-supplier-evaluacion">Evaluación (0-5)</label>
                        <input type="number" id="edit-supplier-evaluacion" name="evaluacion" class="form-input" min="0" max="5" step="0.1">
                        <div class="error-message" id="error-edit-evaluacion"></div>
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
    <link rel="stylesheet" href="{{ asset('css/proveedores/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/proveedores/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/proveedores/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/proveedores/pagination-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/proveedores/proveedores.css') }}">
@endpush