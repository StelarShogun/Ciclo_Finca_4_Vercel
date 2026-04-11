<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editar atributo - Ciclo Finca 4 Admin</title>
    @vite(['resources/css/admin/suppliers/suppliers.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main">
        <div class="form-container">
            <div class="form-header">
                <h1>Editar atributo</h1>
                <p>{{ optional($dimension->category->parent)->name ?? '' }} › {{ $dimension->category->name ?? '' }}</p>
            </div>

            <div class="form-card">
                <form action="{{ route('admin.classifications.dimensions.update', $dimension) }}" method="POST" class="form-body">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="slug">Código interno *</label>
                        <input type="text" id="slug" name="slug" value="{{ old('slug', $dimension->slug) }}" required pattern="[a-z0-9_-]+" maxlength="64">
                        <div class="error-message">{{ $errors->first('slug') }}</div>
                    </div>
                    <div class="form-group">
                        <label for="label">Nombre visible *</label>
                        <input type="text" id="label" name="label" value="{{ old('label', $dimension->label) }}" required maxlength="255">
                        <div class="error-message">{{ $errors->first('label') }}</div>
                    </div>
                    <div class="form-group">
                        <label for="sort_order">Orden</label>
                        <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $dimension->sort_order) }}" min="0">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('admin.classifications.catalog.show', $dimension->category) }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>

</html>
