@extends('admin.layouts.brands')

@section('Titulo pagina', 'Marcas - Ciclo Finca 4 Admin')

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    <div class="brands-container">

        {{-- ==================== HEADER ==================== --}}
        @component('admin.partials.page-header', [
            'title' => 'Gestión de Marcas',
            'description' => 'Administra las marcas asociadas a los productos del inventario.',
        ])
            @slot('actions')
                <div class="brands-actions">
                    <span class="brands-count-badge">
                        <i class="fas fa-tags"></i>
                        {{ $brands->total() }} marca(s)
                    </span>
                    <button class="btn btn-primary" id="btn-nueva-marca">
                        <i class="fas fa-plus"></i> Nueva marca
                    </button>
                </div>
            @endslot
        @endcomponent

        {{-- ==================== FILTROS --}}
        @component('admin.partials.filters', [
            'action' => route('brands.index'),
            'clearUrl' => route('brands.index'),
            'title' => 'Filtros',
        ])
            @slot('fields')
                <div class="filter-group">
                    <label for="buscarNombre">Nombre de la Marca</label>
                    <input type="text" id="buscarNombre" name="name"
                        placeholder="Buscar por nombre..." value="{{ request('name') }}">
                </div>
            @endslot
        @endcomponent

        {{-- ==================== TABLA --}}
        <div class="table-section" data-cf4-ajax-pagination data-cf4-ajax-scroll>
            <div id="cf4-list-fragment">
            <table class="brands-table">
                <thead>
                    <tr>
                        <th>Marca</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr>
                            <td class="brand-name">{{ $brand->name }}</td>
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
                            <td colspan="2" class="brands-empty">No hay marcas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="pagination-wrapper">
                <x-admin.pagination :paginator="$brands" label="marcas" />
            </div>
            </div>
        </div>

    </div>

    {{-- ==================== MODAL NUEVA / EDITAR MARCA --}}
    <div id="modal-marca" class="modal-overlay" style="display:none;">
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modal-titulo">Nueva marca</h2>
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


