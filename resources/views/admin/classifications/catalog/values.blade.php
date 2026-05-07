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
                <x-admin-alert type="success" :message="session('status')" dismissible />
            @endif

            <div class="form-card" style="margin-bottom:1.5rem;">
                <h2 style="font-size:1.1rem; margin-bottom:1rem;">Añadir valor</h2>
                <form action="{{ route('admin.classifications.values.store', $dimension) }}" method="POST" class="form-body">
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
                            <input type="text" id="value" name="value" value="{{ old('value') }}" required maxlength="255" placeholder="Rojo">
                            <div class="error-message">{{ $errors->first('value') }}</div>
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
                                <th style="padding:0.5rem;">Estado</th>
                                <th style="padding:0.5rem;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dimension->values as $val)
                                <tr style="border-bottom:1px solid #f3f4f6; {{ $val->trashed() ? 'opacity:0.5;' : '' }}">
                                    <td style="padding:0.5rem;">{{ $val->value }}</td>
                                    <td style="padding:0.5rem;">
                                        @if ($val->trashed())
                                            <span style="color:#b91c1c; font-weight:600;">Inactivo</span>
                                        @else
                                            <span style="color:#15803d; font-weight:600;">Activo</span>
                                        @endif
                                    </td>
                                    <td style="padding:0.5rem;">
                                        @if (! $val->trashed())
                                            <a href="{{ route('admin.classifications.values.edit', $val) }}" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.85rem;">Editar</a>
                                            <form action="{{ route('admin.classifications.values.destroy', $val) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary"
                                                    style="padding:0.25rem 0.5rem; font-size:0.85rem; color:#b91c1c;"
                                                    data-confirm-title="¿Deseas desactivar este valor?"
                                                    data-confirm="Se desactivará este valor. Los productos que ya lo tenían siguen igual."
                                                >Desactivar</button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.classifications.values.restore', $val) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button
                                                    type="button"
                                                    class="btn btn-primary"
                                                    style="padding:0.25rem 0.5rem; font-size:0.85rem;"
                                                    data-confirm-title="¿Deseas activar este valor?"
                                                    data-confirm="Se activará de nuevo este valor."
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
                <a href="{{ route('admin.classifications.catalog.show', $dimension->category) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a los atributos de este tipo</a>
            </div>
        </div>
    </main>
</body>

</html>
