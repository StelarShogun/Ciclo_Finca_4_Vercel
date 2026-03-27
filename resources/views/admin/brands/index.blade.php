@extends('admin.layouts.brands')

@section('Titulo pagina', 'Marcas - Ciclo Finca 4 Admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/admin/suppliers/suppliers.css') }}">
    @vite(['resources/css/admin/suppliers/suppliers.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    <div class="sales-container">

        {{-- ==================== HEADER ==================== --}}
        <header class="sales-header">
            <div>
                <h1>Gestión de Marcas</h1>
                <p>Administra las marcas de productos</p>
            </div>
            <div class="sales-actions">
                <button class="btn btn-primary" id="btn-nueva-marca">
                    <i class="fas fa-plus"></i> Nueva Marca
                </button>
            </div>
        </header>

        {{-- ==================== KPI --}}
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3 class="kpi-title">Total Marcas</h3>
                    <div class="kpi-icon info"><i class="fas fa-tags"></i></div>
                </div>
                <p class="kpi-value">{{ $brands->total() }}</p>
            </div>
        </div>

        {{-- ==================== FILTROS --}}
        <div class="filters-section">
            <h2 class="filters-title">Filtros</h2>
            <form method="GET" action="{{ route('brands.index') }}" class="filters-grid">
                <div class="filter-group">
                    <label for="buscarNombre">Nombre de la Marca</label>
                    <input type="text" id="buscarNombre" name="name"
                        placeholder="Buscar por nombre..." value="{{ request('name') }}">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                    <a href="{{ route('brands.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Limpiar</a>
                </div>
            </form>
        </div>

        {{-- ==================== TABLA --}}
        <div class="table-section">
            <table class="suppliers-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr>
                            <td>{{ $brand->id }}</td>
                            <td>{{ $brand->name }}</td>
                            <td class="actions-cell">
                                <button class="btn-icon btn-edit"
                                    data-id="{{ $brand->id }}"
                                    data-name="{{ $brand->name }}"
                                    title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-delete"
                                    data-id="{{ $brand->id }}"
                                    data-name="{{ $brand->name }}"
                                    title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center;">No hay marcas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="pagination-wrapper">
                {{ $brands->appends(request()->query())->links() }}
            </div>
        </div>

    </div>

    {{-- ==================== MODAL NUEVA / EDITAR MARCA --}}
    <div id="modal-marca" class="modal-overlay" style="display:none;">
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modal-titulo">Nueva Marca</h2>
                <button class="modal-close" id="btn-cerrar-modal"><i class="fas fa-times"></i></button>
            </div>
            <form id="form-marca">
                @csrf
                <input type="hidden" id="marca-id" value="">
                <div class="form-group">
                    <label for="marca-nombre">Nombre <span class="required">*</span></label>
                    <input type="text" id="marca-nombre" name="name"
                        placeholder="Ej: Trek, Giant, Shimano..." maxlength="100" required>
                    <span class="field-error" id="error-nombre"></span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn-cancelar">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar">Guardar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const modal       = document.getElementById('modal-marca');
    const formMarca   = document.getElementById('form-marca');
    const inputId     = document.getElementById('marca-id');
    const inputNombre = document.getElementById('marca-nombre');
    const errorNombre = document.getElementById('error-nombre');
    const modalTitulo = document.getElementById('modal-titulo');

    const openModal = () => { modal.style.display = 'flex'; };
    const closeModal = () => {
        modal.style.display = 'none';
        formMarca.reset();
        inputId.value = '';
        errorNombre.textContent = '';
        modalTitulo.textContent = 'Nueva Marca';
    };

    document.getElementById('btn-nueva-marca').addEventListener('click', () => {
        inputId.value = '';
        openModal();
    });
    document.getElementById('btn-cerrar-modal').addEventListener('click', closeModal);
    document.getElementById('btn-cancelar').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    // Edit buttons
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            inputId.value     = btn.dataset.id;
            inputNombre.value = btn.dataset.name;
            modalTitulo.textContent = 'Editar Marca';
            openModal();
        });
    });

    // Delete buttons
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', async () => {
            const confirmed = await Swal.fire({
                title: '¿Eliminar marca?',
                text: `"${btn.dataset.name}" será eliminada.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c',
            });
            if (!confirmed.isConfirmed) return;

            const res = await fetch(`/brands/${btn.dataset.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire('Eliminada', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', 'No se pudo eliminar la marca.', 'error');
            }
        });
    });

    // Form submit (create / update)
    formMarca.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorNombre.textContent = '';

        const id     = inputId.value;
        const url    = id ? `/brands/${id}` : '/brands';
        const method = id ? 'PUT' : 'POST';

        const res = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name: inputNombre.value }),
        });

        const data = await res.json();

        if (data.success) {
            Swal.fire('Éxito', data.message, 'success').then(() => location.reload());
        } else if (data.errors) {
            errorNombre.textContent = data.errors.name?.[0] ?? '';
        } else {
            Swal.fire('Error', 'Ocurrió un error inesperado.', 'error');
        }
    });
</script>
@endpush
