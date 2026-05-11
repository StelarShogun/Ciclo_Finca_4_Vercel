<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editar valor - Ciclo Finca 4 Admin</title>
    @vite(['resources/css/admin/suppliers/suppliers.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main">
        <div class="form-container">
            <div class="form-header">
                <h1>Editar valor</h1>
                <p>Atributo «{{ $dimension->label }}» — {{ optional($dimension->category->parent)->name ?? '' }} › {{ $dimension->category->name ?? '' }}</p>
            </div>

            <div class="form-card">
                <form action="{{ route('admin.classifications.values.update', $value) }}" method="POST" class="form-body">
                    @csrf
                    @method('PUT')
                    @if ($errors->any())
                        <x-admin-alert type="error" title="Revisa los campos marcados antes de continuar.">
                            <ul style="margin: 0; padding-left: 1.25rem;">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </x-admin-alert>
                    @endif
                    <div class="form-group">
                        <label for="value">Valor (lo que verá el cliente) *</label>
                        <input type="text" id="value" name="value" value="{{ old('value', $value->value) }}" required maxlength="255">
                        <div class="error-message">{{ $errors->first('value') }}</div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('admin.classifications.values.index', $dimension) }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>

</html>
