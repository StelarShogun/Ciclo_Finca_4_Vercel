<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Opciones por tipo de producto - Ciclo Finca 4 Admin</title>
    @vite(['resources/css/admin/suppliers/suppliers.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main">
        <div class="form-container">
            <div class="form-header">
                <h1>Opciones por tipo de producto</h1>
                <p>Definís los <strong>atributos</strong> (Color, Talla…) y los <strong>valores</strong> de cada uno por <strong>tipo</strong> de producto (ej. MTB). Al cargar un producto, elegís un valor por atributo.</p>
            </div>

            <div class="form-card">
                <div class="form-body">
                    @if ($subcategories->isEmpty())
                        <p>Todavía no hay tipos de producto. Creá uno en <a href="{{ route('categories.subcategories.create') }}">categorías</a>.</p>
                    @else
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:2px solid #e5e7eb; text-align:left;">
                                    <th style="padding:0.75rem;">Tipo de producto</th>
                                    <th style="padding:0.75rem;">Rubro</th>
                                    <th style="padding:0.75rem;">Atributos definidos</th>
                                    <th style="padding:0.75rem;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($subcategories as $sub)
                                    <tr style="border-bottom:1px solid #f3f4f6;">
                                        <td style="padding:0.75rem;">{{ $sub->name }}</td>
                                        <td style="padding:0.75rem;">{{ optional($sub->parent)->name ?? '—' }}</td>
                                        <td style="padding:0.75rem;">{{ $sub->classification_dimensions_count }}</td>
                                        <td style="padding:0.75rem;">
                                            <a href="{{ route('admin.classifications.catalog.show', $sub) }}" class="btn btn-primary" style="display:inline-flex; padding:0.35rem 0.75rem; text-decoration:none; border-radius:6px;">
                                                Gestionar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <div style="margin-top:1.5rem;">
                <a href="{{ route('admin.product-classifications.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Ver productos y opciones elegidas</a>
                <a href="{{ route('inventory') }}" class="btn btn-secondary"><i class="fas fa-box"></i> Inventario</a>
            </div>
        </div>
    </main>
</body>

</html>
