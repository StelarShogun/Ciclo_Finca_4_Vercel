<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Atributos para {{ $category->name }} - Ciclo Finca 4 Admin</title>
    @vite(['resources/css/admin/suppliers/suppliers.css', 'resources/js/admin/classifications/catalog.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main">
        <div class="form-container">
            <div class="form-header">
                <h1>Atributos para: {{ $category->name }}</h1>
                <p>Un <strong>atributo</strong> es el tipo de dato (Color, Talla…). Cada atributo tiene <strong>valores</strong> (Rojo, M…). {{ optional($category->parent)->name ?? '' }} › <strong>{{ $category->name }}</strong></p>
            </div>

            @if (session('status'))
                <div class="success-message" style="margin-bottom:1rem;"><i class="fas fa-check-circle"></i> {{ session('status') }}</div>
            @endif

            <div class="form-card" style="margin-bottom:1.5rem;">
                <h2 style="font-size:1.1rem; margin-bottom:1rem;">Añadir atributo (ej. Color, Talla)</h2>
                <form action="{{ route('admin.classifications.dimensions.store', $category) }}" method="POST" class="form-body">
                    @csrf
                    <div class="form-row" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
                        <div class="form-group">
                            <label for="slug">Código interno (sin espacios, para el sistema) *</label>
                            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" required placeholder="color" pattern="[a-z0-9_-]+" maxlength="64">
                            <div class="error-message">{{ $errors->first('slug') }}</div>
                        </div>
                        <div class="form-group">
                            <label for="label">Nombre del atributo (vos y la tienda) *</label>
                            <input type="text" id="label" name="label" value="{{ old('label') }}" required maxlength="255" placeholder="Color">
                            <div class="error-message">{{ $errors->first('label') }}</div>
                        </div>
                        <div class="form-group">
                            <label for="sort_order">Orden</label>
                            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Añadir</button>
                    </div>
                </form>
            </div>

            <div class="form-card">
                <h2 style="font-size:1.1rem; margin-bottom:1rem;">Atributos cargados</h2>
                @if ($attributes->isEmpty())
                    <p>Todavía no hay atributos para esta subcategoría. Añadí al menos uno (ej. Color) y después sus valores.</p>
                @else
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:2px solid #e5e7eb; text-align:left;">
                                <th style="padding:0.5rem;">Atributo</th>
                                <th style="padding:0.5rem;">Código</th>
                                <th style="padding:0.5rem;">Cantidad de valores</th>
                                <th style="padding:0.5rem;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($attributes as $dim)
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:0.5rem;">{{ $dim->label }}</td>
                                    <td style="padding:0.5rem;"><code>{{ $dim->slug }}</code></td>
                                    <td style="padding:0.5rem;">{{ $dim->values_count }}</td>
                                    <td style="padding:0.5rem;">
                                        <a href="{{ route('admin.classifications.values.index', $dim) }}" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.85rem;">Valores</a>
                                        <a href="{{ route('admin.classifications.dimensions.edit', $dim) }}" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.85rem;">Editar</a>
                                        <form action="{{ route('admin.classifications.dimensions.destroy', $dim) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.85rem; color:#b91c1c;" data-confirm="Se ocultará este atributo. Los productos que ya tenían un valor siguen igual hasta que los edites.">Ocultar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div style="margin-top:1.5rem;">
                <a href="{{ route('admin.classifications.catalog.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista de tipos</a>
            </div>
        </div>
    </main>
</body>

</html>
