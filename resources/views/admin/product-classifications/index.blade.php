<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Características por producto - Ciclo Finca 4 Admin</title>
    @vite(['resources/css/admin/suppliers/suppliers.css', 'resources/js/shared/ajax-pagination.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main">
        <div class="form-container">
            @component('admin.partials.page-header', ['title' => 'Características por producto'])
                <p>
                    Consulta y administra los valores asignados a cada producto según sus atributos, como color, talla o
                    material.
                    Solo se muestran productos asociados a un tipo concreto dentro del catálogo.
                </p>
            @endcomponent

            @if (session('status'))
                <x-admin-alert type="success" :message="session('status')" dismissible />
            @endif
            @if (session('error'))
                <x-admin-alert type="error" :message="session('error')" />
            @endif

            <div class="form-card">
                <div class="form-body">
                    @if ($products->isEmpty())
                        <x-admin-alert type="info" title="No hay registros disponibles para mostrar." dismissible>
                            <div>
                                <p style="margin: 0;"><strong>Todo está bien:</strong> solo faltan productos
                                    correctamente ubicados en el catálogo. En <strong>Inventario</strong>, al crear o
                                    editar un producto, completa la categoría padre <em>y</em> el tipo concreto (p. ej.,
                                    Bicicletas → MTB). Esto habilita atributos como color, talla, etc.</p>
                                <p style="margin: 0.75rem 0 0;">Si el producto queda solo en la categoría padre (sin
                                    tipo concreto), no entra en esta lista.</p>
                            </div>
                        </x-admin-alert>
                    @else
                        <div data-cf4-ajax-pagination data-cf4-ajax-scroll>
                        <div id="cf4-list-fragment">
                        <div style="overflow-x: auto;">
                            <table class="table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="text-align: left; border-bottom: 2px solid #e5e7eb;">
                                        <th style="padding: 0.75rem;">Producto</th>
                                        <th style="padding: 0.75rem;">Categoría -> Subcategoría</th>
                                        <th style="padding: 0.75rem;">Atributo → valor</th>
                                        <th style="padding: 0.75rem;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($products as $product)
                                        <tr style="border-bottom: 1px solid #f3f4f6;">
                                            <td style="padding: 0.75rem;">{{ $product->name }}</td>
                                            <td style="padding: 0.75rem;">
                                                @if ($product->category)
                                                    {{ optional($product->category->parent)->name ?? '—' }}
                                                    <span class="text-muted">-></span>
                                                    {{ $product->category->name }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td style="padding: 0.75rem; font-size: 0.9rem;">
                                                @forelse ($product->classificationValues as $cv)
                                                    <span style="display: inline-block; margin-right: 0.5rem;">
                                                        <strong>{{ $cv->dimension->label ?? '—' }}:</strong>
                                                        {{ $cv->value }}
                                                    </span>
                                                @empty
                                                    <em>Sin asignar</em>
                                                @endforelse
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <a href="{{ route('admin.products.classifications.edit', $product) }}"
                                                    class="btn btn-primary"
                                                    style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.4rem 0.75rem; text-decoration: none; border-radius: 6px;">
                                                    <i class="fas fa-sliders-h"></i> Editar
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 1rem;">
                            <x-admin.pagination :paginator="$products" label="productos" />
                        </div>
                        </div>
                        </div>
                    @endif
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <a href="{{ route('inventory') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al inventario
                </a>
            </div>
        </div>
    </main>
</body>

</html>
