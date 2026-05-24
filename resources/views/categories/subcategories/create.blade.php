@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Crear subcategoría - Ciclo Finca 4 Admin')

@section('aside')
    @include('admin.parts.aside')
@endsection

@push('styles')
    @vite(['resources/css/admin/suppliers/suppliers.css'])
@endpush

@section('contenido')
    <div class="form-container">
        <nav class="admin-breadcrumb" aria-label="Migas de pan">
            <a href="{{ route('inventory') }}">Inventario</a>
            <span class="sep">/</span>
            <span>Crear subcategoría</span>
        </nav>

        @component('admin.partials.page-header', ['title' => 'Crear subcategoría'])
            <p>
                Registra una subcategoría dentro de una categoría principal para clasificar los productos
                con mayor precisión.
            </p>
        @endcomponent

        <div class="form-card">
            <form
                action="{{ route('categories.subcategories.store') }}"
                method="POST"
                class="form-body"
                data-cf4-confirm
                data-confirm-title="¿Guardar esta subcategoría?"
                data-confirm-text="Se asociará a la categoría padre seleccionada."
                data-confirm-button="Sí, guardar"
            >
                @csrf

                <div class="form-group">
                    <label for="name">Nombre de la subcategoría *</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        placeholder="Ej. MTB, Ruta urbana">
                    <div class="error-message">{{ $errors->first('name') }}</div>
                </div>

                <div class="form-group">
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description" rows="3"
                        placeholder="Opcional.">{{ old('description') }}</textarea>
                    <div class="error-message">{{ $errors->first('description') }}</div>
                </div>

                <div class="form-group">
                    <label for="parent_category_id">Categoría padre *</label>
                    <select id="parent_category_id" name="parent_category_id" required>
                        <option value="">Selecciona una categoría padre</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->category_id }}" @selected(old('parent_category_id') == $category->category_id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="error-message">{{ $errors->first('parent_category_id') }}</div>
                    <small class="form-text text-muted">
                        ¿Falta una categoría padre?
                        <a href="{{ route('categories.parents.create') }}">Crear categoría padre</a>.
                    </small>
                </div>

                <div class="form-group optional">
                    <label>Subcategorías actuales del padre seleccionado</label>
                    <div id="parent-subcategories-hint" class="info-section">
                        <p>Selecciona una categoría padre para ver sus subcategorías actuales.</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus" aria-hidden="true"></i> Guardar subcategoría
                    </button>

                    <a href="{{ route('inventory') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver
                    </a>
                </div>
            </form>
        </div>

        <div class="form-card" style="margin-top: 18px;">
            <div class="table-header"
                style="padding: 0 0 12px 0; border-bottom: 1px solid var(--border-color); margin-bottom: 12px;">
                <h3 style="margin: 0;"><i class="fas fa-sitemap" aria-hidden="true"></i> Jerarquía de categorías</h3>
            </div>

            <div class="sales-table-container">
                <table class="sales-table admin-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Categoría padre</th>
                            <th>Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categoriesHierarchy as $row)
                            <tr>
                                <td>
                                    @if (is_null($row->parent_category_id))
                                        <strong>{{ $row->name }}</strong>
                                    @else
                                        — {{ $row->name }}
                                    @endif
                                </td>
                                <td>{{ $row->parent->name ?? '—' }}</td>
                                <td>{{ is_null($row->parent_category_id) ? 'Padre' : 'Subcategoría' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">No hay categorías registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($categoriesHierarchy->total() > 0)
                <div class="category-hierarchy-pagination" style="margin-top: 12px;">
                    <x-pagination :paginator="$categoriesHierarchy" label="categorías" />
                </div>
            @endif
        </div>
    </div>

    <script type="application/json" id="subcategories-by-parent-data">@json($subcategoriesByParent)</script>
@endsection

@push('scripts')
    @vite([
        'resources/js/admin/classifications/forms.js',
        'resources/js/admin/categories/category-subcategory-form.js',
    ])
@endpush
