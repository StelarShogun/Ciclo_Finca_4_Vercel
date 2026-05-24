<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Opciones por tipo de producto - Ciclo Finca 4 Admin</title>
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/components/page-header.css', 'resources/css/admin/suppliers/suppliers.css'])
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main admin-main--content">
        <div class="admin-content-wrapper">
            <div class="form-container">
            @component('admin.partials.page-header', [
                'title' => 'Gestión de opciones por tipo de producto',
            ])
                <p>Define los atributos y valores disponibles para cada tipo de producto, como color, talla o material, para
                    utilizarlos al registrar productos en el inventario.</p>
            @endcomponent

            <div class="form-card" @if (! $subcategories->isEmpty()) style="padding: 0; overflow: hidden;" @endif>
                <div class="form-body">
                    @if ($subcategories->isEmpty())
                        <x-admin-alert type="info" title="No hay registros disponibles para mostrar." dismissible>
                            Aún no hay tipos de producto. Crea uno en <a
                                href="{{ route('categories.subcategories.create') }}">categorías</a>.
                        </x-admin-alert>
                    @else
                        <div class="admin-table-scroll">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Categoría</th>
                                        <th>Subcategoría</th>
                                        <th>Atributos definidos</th>
                                        <th class="admin-table__col--actions" scope="col">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subcategories as $sub)
                                        <tr>
                                            <td>{{ optional($sub->parent)->name ?? '—' }}</td>
                                            <td>{{ $sub->name }}</td>
                                            <td>{{ $sub->classification_dimensions_count }}</td>
                                            <td class="admin-table__col--actions">
                                                <a href="{{ route('admin.classifications.catalog.show', $sub) }}"
                                                    class="btn btn-primary"
                                                    style="display:inline-flex; padding:0.35rem 0.75rem; text-decoration:none; border-radius:6px;">
                                                    Gestionar
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div style="margin-top:1.5rem;">
                <a href="{{ route('admin.product-classifications.index') }}" class="btn btn-secondary"><i
                        class="fas fa-arrow-left"></i> Ver productos y opciones elegidas</a>
                <a href="{{ route('inventory') }}" class="btn btn-secondary"><i class="fas fa-box"></i> Inventario</a>
            </div>
            </div>
        </div>
    </main>
</body>

</html>
