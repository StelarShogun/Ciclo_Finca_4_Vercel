<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editar atributo - Ciclo Finca 4 Admin</title>

    @include('admin.partials.cf4-theme-head')

    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/components/page-header.css', 'resources/css/admin/suppliers/suppliers.css', 'resources/js/admin/classifications/forms.js'])
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main admin-main--content">
        <div class="admin-content-wrapper">
            <div class="form-container">
            @component('admin.partials.page-header', ['title' => 'Editar atributo'])
                <p>{{ optional($dimension->category->parent)->name ?? '' }} › {{ $dimension->category->name ?? '' }}</p>
            @endcomponent

            <div class="form-card">
                <form action="{{ route('admin.classifications.dimensions.update', $dimension) }}" method="POST" class="form-body">
                    @csrf
                    @method('PUT')
                    @if ($errors->any())
                        <x-admin-alert type="error" title="Revisa los campos marcados antes de continuar.">
                            <ul style="margin: 0; padding-left: 1.25rem;">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </x-admin-alert>
                    @endif
                    <div class="form-group">
                        <label for="label">Nombre del atributo *</label>
                        <input type="text" id="label" name="label" value="{{ old('label', $dimension->label) }}" required maxlength="255">
                        <div class="error-message">{{ $errors->first('label') }}</div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary cf4-inline-action">Guardar</button>
                        <a href="{{ route('admin.classifications.catalog.show', $dimension->category) }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
            </div>
        </div>
    </main>

    @include('admin.partials.cf4-theme-scripts')
</body>

</html>
