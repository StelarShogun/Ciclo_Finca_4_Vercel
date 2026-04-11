<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Valores del atributo {{ $dimension->label }} - Ciclo Finca 4 Admin</title>
    @vite(['resources/css/admin/suppliers/suppliers.css', 'resources/js/admin/classifications/catalog.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main">
        <div class="form-container">
            <div class="form-header">
                <h1>Valores del atributo «{{ $dimension->label }}»</h1>
                <p>{{ optional($dimension->category->parent)->name ?? '' }} › {{ $dimension->category->name ?? '' }}</p>
            </div>

            @if (session('status'))
                <div class="success-message" style="margin-bottom:1rem;"><i class="fas fa-check-circle"></i> {{ session('status') }}</div>
            @endif

            <div class="form-card" style="margin-bottom:1.5rem;">
                <h2 style="font-size:1.1rem; margin-bottom:1rem;">Añadir valor</h2>
                <form action="{{ route('admin.classifications.values.store', $dimension) }}" method="POST" class="form-body">
                    @csrf
                    <div class="form-row" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
                        <div class="form-group">
                            <label for="value">Valor (lo que verá el cliente) *</label>
                            <input type="text" id="value" name="value" value="{{ old('value') }}" required maxlength="255" placeholder="Rojo">
                            <div class="error-message">{{ $errors->first('value') }}</div>
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
                <h2 style="font-size:1.1rem; margin-bottom:1rem;">Valores cargados</h2>
                @if ($dimension->values->isEmpty())
                    <p>Todavía no hay valores. Agregá al menos uno (ej. Rojo, Azul).</p>
                @else
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:2px solid #e5e7eb; text-align:left;">
                                <th style="padding:0.5rem;">Valor</th>
                                <th style="padding:0.5rem;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dimension->values as $val)
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:0.5rem;">{{ $val->value }}</td>
                                    <td style="padding:0.5rem;">
                                        <a href="{{ route('admin.classifications.values.edit', $val) }}" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.85rem;">Editar</a>
                                        <form action="{{ route('admin.classifications.values.destroy', $val) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.85rem; color:#b91c1c;" data-confirm="Se ocultará este valor. Los productos que ya lo tenían siguen igual hasta que los edites.">Ocultar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div style="margin-top:1.5rem;">
                <a href="{{ route('admin.classifications.catalog.show', $dimension->category) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a los atributos de este tipo</a>
            </div>
        </div>
    </main>
</body>

</html>
