@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Crear categoría - Ciclo Finca 4 Admin')

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
            <span>Crear categoría</span>
        </nav>

        @component('admin.partials.page-header', ['title' => 'Crear categoría'])
            <p>
                Definí una categoría principal del catálogo para organizar el inventario.
                Luego podés agregar subcategorías y asignar productos.
            </p>
        @endcomponent

        <div class="form-card">
            <form
                id="create-category-form"
                action="{{ route('categories.parents.store') }}"
                method="POST"
                class="form-body"
                data-cf4-confirm
                data-confirm-title="¿Guardar esta categoría?"
                data-confirm-text="Se creará una nueva categoría padre del catálogo."
                data-confirm-button="Sí, guardar"
            >
                @csrf

                <div class="form-group">
                    <label for="name">Nombre de la categoría *</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        placeholder="Ej. Iluminación, Llantas"
                        autocomplete="off"
                    >
                    <div class="error-message">{{ $errors->first('name') }}</div>
                </div>

                <div class="form-group">
                    <label for="description">Descripción</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Opcional."
                    >{{ old('description') }}</textarea>
                    <div class="error-message">{{ $errors->first('description') }}</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus" aria-hidden="true"></i> Guardar categoría
                    </button>

                    <a href="{{ route('inventory') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver al inventario
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/classifications/forms.js'])
@endpush
