<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Atributos para {{ $category->name }} - Ciclo Finca 4 Admin</title>
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/components/page-header.css', 'resources/css/admin/suppliers/suppliers.css', 'resources/js/admin/classifications/catalog.js'])
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main admin-main--content">
        <div class="admin-content-wrapper">
            <div class="form-container">
            <nav class="reports-breadcrumb" aria-label="Migas de pan">
                <a href="{{ route('admin.classifications.catalog.index') }}">Opciones por tipo</a>
                <span class="sep">/</span>
                <span>{{ $category->name }}</span>
            </nav>

            @component('admin.partials.page-header', ['title' => 'Atributos para: ' . $category->name])
                <p>
                    Un <strong>atributo</strong> es el tipo de dato, como Color, Talla o Material.
                    Cada atributo puede tener <strong>valores</strong>, como Rojo, M o Algodón.

                    @if ($category->parent)
                        Categoría: {{ $category->parent->name }} ›
                        <strong>{{ $category->name }}</strong>
                    @else
                        Categoría: <strong>{{ $category->name }}</strong>
                    @endif
                </p>
            @endcomponent

            @if (session('status'))
                <x-admin-alert type="success" :message="session('status')" dismissible />
            @endif

            <div class="form-card" style="margin-bottom:1.5rem;">
                <h2 style="font-size:1.1rem; margin-bottom:1rem;">Añadir atributo (ej. Color, Talla)</h2>

                <form action="{{ route('admin.classifications.dimensions.store', $category) }}" method="POST"
                    class="form-body">
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
                            <label for="label">Nombre del atributo *</label>
                            <input type="text" id="label" name="label" value="{{ old('label') }}" required
                                maxlength="255" placeholder="Color">
                            <div class="error-message">{{ $errors->first('label') }}</div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Añadir
                        </button>
                    </div>
                </form>
            </div>

            <div class="form-card" @if (! $attributes->isEmpty()) style="padding: 0; overflow: hidden;" @endif>
                <h2 style="font-size:1.1rem; margin-bottom:1rem; @if (! $attributes->isEmpty()) padding: 25px 25px 0; margin: 0; @endif">Atributos cargados</h2>

                @if ($attributes->isEmpty())
                    <p>
                        Todavía no hay atributos para esta subcategoría. Añadí al menos uno
                        (ej. Color) y después sus valores.
                    </p>
                @else
                    <div class="admin-table-scroll">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Atributo</th>
                                    <th>Cantidad de valores</th>
                                    <th>Estado</th>
                                    <th class="admin-table__col--actions" scope="col">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($attributes as $dim)
                                    <tr @class(['is-muted' => $dim->trashed()]) style="{{ $dim->trashed() ? 'opacity:0.5;' : '' }}">
                                        <td>{{ $dim->label }}</td>
                                        <td>{{ $dim->values_count }}</td>
                                        <td>
                                            @if ($dim->trashed())
                                                <span class="status-badge status-banned">Inactivo</span>
                                            @else
                                                <span class="status-badge status-active">Activo</span>
                                            @endif
                                        </td>
                                        <td class="admin-table__col--actions">
                                            <a href="{{ route('admin.classifications.values.index', $dim) }}"
                                                class="btn btn-secondary"
                                                style="padding:0.25rem 0.5rem; font-size:0.85rem;">
                                                Valores
                                            </a>

                                            @if (!$dim->trashed())
                                                <a href="{{ route('admin.classifications.dimensions.edit', $dim) }}"
                                                    class="btn btn-secondary"
                                                    style="padding:0.25rem 0.5rem; font-size:0.85rem;">
                                                    Editar
                                                </a>

                                                <form
                                                    action="{{ route('admin.classifications.dimensions.destroy', $dim) }}"
                                                    method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="button" class="btn btn-secondary"
                                                        style="padding:0.25rem 0.5rem; font-size:0.85rem; color:#b91c1c;"
                                                        data-confirm-title="¿Deseas desactivar este atributo?"
                                                        data-confirm="Se desactivará este atributo. Los productos que ya tenían un valor siguen igual.">
                                                        Desactivar
                                                    </button>
                                                </form>
                                            @else
                                                <form
                                                    action="{{ route('admin.classifications.dimensions.restore', $dim) }}"
                                                    method="POST" style="display:inline;">
                                                    @csrf

                                                    <button type="button" class="btn btn-primary"
                                                        style="padding:0.25rem 0.5rem; font-size:0.85rem;"
                                                        data-confirm-title="¿Deseas activar este atributo?"
                                                        data-confirm="Se activará de nuevo este atributo.">
                                                        Activar
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div style="margin-top:1.5rem;">
                <a href="{{ route('admin.classifications.catalog.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a la lista de tipos
                </a>
            </div>
            </div>
        </div>
    </main>
</body>

</html>
