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
                <x-admin-alert type="success" :message="session('status')" dismissible />
            @endif

            <div class="form-card" style="margin-bottom:1.5rem;">
                <h2 style="font-size:1.1rem; margin-bottom:1rem;">Añadir atributo (ej. Color, Talla)</h2>
                <form action="{{ route('admin.classifications.dimensions.store', $category) }}" method="POST" class="form-body">
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
                            <input type="text" id="label" name="label" value="{{ old('label') }}" required maxlength="255" placeholder="Color">
                            <div class="error-message">{{ $errors->first('label') }}</div>
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
                                <th style="padding:0.5rem;">Cantidad de valores</th>
                                <th style="padding:0.5rem;">Estado</th>
                                <th style="padding:0.5rem;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($attributes as $dim)
                                <tr style="border-bottom:1px solid #f3f4f6; {{ $dim->trashed() ? 'opacity:0.5;' : '' }}">
                                    <td style="padding:0.5rem;">{{ $dim->label }}</td>
                                    <td style="padding:0.5rem;">{{ $dim->values_count }}</td>
                                    <td style="padding:0.5rem;">
                                        @if ($dim->trashed())
                                            <span style="color:#b91c1c; font-weight:600;">Inactivo</span>
                                        @else
                                            <span style="color:#15803d; font-weight:600;">Activo</span>
                                        @endif
                                    </td>
                                    <td style="padding:0.5rem;">
                                        <a href="{{ route('admin.classifications.values.index', $dim) }}" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.85rem;">Valores</a>

                                        @if (! $dim->trashed())
                                            <a href="{{ route('admin.classifications.dimensions.edit', $dim) }}" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.85rem;">Editar</a>
                                            <form action="{{ route('admin.classifications.dimensions.destroy', $dim) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary"
                                                    style="padding:0.25rem 0.5rem; font-size:0.85rem; color:#b91c1c;"
                                                    data-confirm-title="¿Deseas desactivar este atributo?"
                                                    data-confirm="Se desactivará este atributo. Los productos que ya tenían un valor siguen igual."
                                                >Desactivar</button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.classifications.dimensions.restore', $dim) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button
                                                    type="button"
                                                    class="btn btn-primary"
                                                    style="padding:0.25rem 0.5rem; font-size:0.85rem;"
                                                    data-confirm-title="¿Deseas activar este atributo?"
                                                    data-confirm="Se activará de nuevo este atributo."
                                                >Activar</button>
                                            </form>
                                        @endif
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
