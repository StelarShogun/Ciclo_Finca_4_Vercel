<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crear Subcategoría - Ciclo Finca 4 Admin</title>

    @vite(['resources/css/admin.css', 'resources/css/admin-pages.css', 'resources/css/suppliers/suppliers.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-layout">
    @include('partes.aside')

    <main class="admin-main">
        <div class="form-container">
            <div class="form-header">
                <h1>Crear Subcategoría</h1>
                <p>Clasifica productos de forma más específica</p>
            </div>

            <div class="form-card">
                @if (session('status'))
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> {{ session('status') }}
                    </div>
                @endif

                <form action="{{ route('categories.subcategories.store') }}" method="POST" class="form-body">
                    @csrf

                    <div class="form-group">
                        <label for="name">Nombre de la subcategoría *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            placeholder="e.g., MT (Mountain)">
                        <div class="error-message">{{ $errors->first('name') }}</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción</label>
                        <textarea id="description" name="description" rows="3"
                            placeholder="Opcional. {{ old('description') ? '' : 'Describe brevemente la subcategoría.' }}">{{ old('description') }}</textarea>
                        <div class="error-message">{{ $errors->first('description') }}</div>
                    </div>

                    <div class="form-group">
                        <label for="parent_category_id">Categoría padre *</label>
                        <select id="parent_category_id" name="parent_category_id" required>
                            <option value="">Seleccione una categoría padre</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->category_id }}"
                                    @selected(old('parent_category_id') == $category->category_id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="error-message">{{ $errors->first('parent_category_id') }}</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Guardar subcategoría
                        </button>

                        <a href="{{ route('inventory') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>

</html>

