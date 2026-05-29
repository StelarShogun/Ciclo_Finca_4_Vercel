<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Valores del atributo {{ $dimension->label }} - Ciclo Finca 4 Admin</title>

    @include('admin.partials.cf4-theme-head')

    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/components/page-header.css', 'resources/css/admin/suppliers/suppliers.css', 'resources/js/admin/classifications/catalog.js', 'resources/js/admin/classifications/forms.js'])
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main admin-main--content">
        <div class="admin-content-wrapper">
            <div class="form-container">
            <nav class="reports-breadcrumb" aria-label="Migas de pan">
                <a href="{{ route('admin.classifications.catalog.index') }}">Opciones por tipo</a>
                <span class="sep">/</span>
                <a href="{{ route('admin.classifications.catalog.show', $dimension->category) }}">{{ $dimension->category->name }}</a>
                <span class="sep">/</span>
                <span>{{ $dimension->label }}</span>
            </nav>

            @component('admin.partials.page-header', [
                'title' => 'Valores del atributo «' . $dimension->label . '»',
            ])
                <p>
                    Administra los valores disponibles para este atributo dentro de
                    {{ optional($dimension->category->parent)->name ?? 'la categoría' }} ›
                    {{ $dimension->category->name ?? 'el tipo de producto' }}.
                </p>
            @endcomponent

            @if (session('status'))
                <x-admin-alert type="success" :message="session('status')" dismissible />
            @endif

            <div class="form-card" style="margin-bottom:1.5rem;">
                <h2 style="font-size:1.1rem; margin-bottom:1rem;">Añadir valor</h2>
                <form action="{{ route('admin.classifications.values.store', $dimension) }}" method="POST"
                    class="form-body"
                    data-cf4-confirm
                    data-confirm-title="¿Añadir valor?"
                    data-confirm-text="Se agregará un nuevo valor al atributo.">
                    @csrf
                    @if ($errors->any())
                        <x-admin-alert type="error" title="Revisa los campos marcados antes de continuar.">
                            <ul style="margin: 0; padding-left: 1.25rem;">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </x-admin-alert>
                    @endif
                    <div class="form-row" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
                        <div class="form-group">
                            <label for="value">Valor (lo que verá el cliente) *</label>
                            <input type="text" id="value" name="value" value="{{ old('value') }}" required
                                maxlength="255" placeholder="Rojo">
                            <div class="error-message">{{ $errors->first('value') }}</div>
                        </div>
                        <button type="submit" class="btn btn-primary cf4-inline-action"><i class="fas fa-plus"></i> Añadir</button>
                    </div>
                </form>
            </div>

            <div class="form-card" @if (! $dimension->values->isEmpty()) style="padding: 0; overflow: hidden;" @endif>
                <h2 style="font-size:1.1rem; margin-bottom:1rem; @if (! $dimension->values->isEmpty()) padding: 25px 25px 0; margin: 0; @endif">Valores cargados</h2>
                @if ($dimension->values->isEmpty())
                    <p>Todavía no hay valores. Agregá al menos uno (ej. Rojo, Azul).</p>
                @else
                    <div class="admin-table-scroll">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Valor</th>
                                    <th>Estado</th>
                                    <th class="admin-table__col--actions" scope="col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dimension->values as $val)
                                    <tr style="{{ $val->trashed() ? 'opacity:0.5;' : '' }}">
                                        <td>{{ $val->value }}</td>
                                        <td>
                                            @if ($val->trashed())
                                                <span class="status-badge status-banned">Inactivo</span>
                                            @else
                                                <span class="status-badge status-active">Activo</span>
                                            @endif
                                        </td>
                                        <td class="admin-table__col--actions">
                                            <div class="actions-container">
                                            @if (!$val->trashed())
                                                <a href="{{ route('admin.classifications.values.edit', $val) }}"
                                                    class="action-btn edit"
                                                    title="Editar valor">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.classifications.values.destroy', $val) }}"
                                                    method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="action-btn delete"
                                                        title="Desactivar valor"
                                                        data-confirm-title="¿Deseas desactivar este valor?"
                                                        data-confirm="Se desactivará este valor. Los productos que ya lo tenían siguen igual.">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.classifications.values.restore', $val) }}"
                                                    method="POST" style="display:inline;">
                                                    @csrf
                                                    <button type="button" class="action-btn view"
                                                        title="Activar valor"
                                                        data-confirm-title="¿Deseas activar este valor?"
                                                        data-confirm="Se activará de nuevo este valor.">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div style="margin-top:1.5rem;">
                <a href="{{ route('admin.classifications.catalog.show', $dimension->category) }}"
                    class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a los atributos de este tipo</a>
            </div>
            </div>
        </div>
    </main>

    @include('admin.partials.cf4-theme-scripts')
</body>

</html>
