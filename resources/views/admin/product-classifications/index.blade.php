<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Características por producto - Ciclo Finca 4 Admin</title>
    @vite(['resources/css/admin/suppliers/suppliers.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main">
        <div class="form-container">
            <div class="form-header">
                <h1>Características por producto</h1>
                <p>Cada producto puede tener un <strong>valor</strong> por <strong>atributo</strong> (ej. Color: Rojo). Solo aparecen productos en <strong>subcategoría</strong> (ej. «MTB», no solo «Bicicletas»).</p>
            </div>

            @if (session('status'))
                <div class="success-message" style="margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i> {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="error-message" style="margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            <div class="form-card">
                <div class="form-body">
                    @if ($products->isEmpty())
                        <p><strong>No hay nada mal:</strong> solo faltan productos bien ubicados en el catálogo. En <strong>Inventario</strong>, al crear o editar, completá el rubro <em>y</em> el tipo concreto (ej. Bicicletas → MTB). Así podés usar color, talla, etc.</p>
                        <p style="margin-top: 0.75rem;">Si el producto queda solo en el rubro grande, no entra en esta lista.</p>
                    @else
                        <div style="overflow-x: auto;">
                            <table class="table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="text-align: left; border-bottom: 2px solid #e5e7eb;">
                                        <th style="padding: 0.75rem;">Producto</th>
                                        <th style="padding: 0.75rem;">En el catálogo</th>
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
                                                    <span class="text-muted">›</span>
                                                    {{ $product->category->name }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td style="padding: 0.75rem; font-size: 0.9rem;">
                                                @forelse ($product->classificationValues as $cv)
                                                    <span style="display: inline-block; margin-right: 0.5rem;">
                                                        <strong>{{ $cv->dimension->label ?? '—' }}:</strong> {{ $cv->value }}
                                                    </span>
                                                @empty
                                                    <em>Sin asignar</em>
                                                @endforelse
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <a href="{{ route('admin.products.classifications.edit', $product) }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.4rem 0.75rem; text-decoration: none; border-radius: 6px;">
                                                    <i class="fas fa-sliders-h"></i> Editar
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 1rem;">
                            {{ $products->links() }}
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
