<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Valores por atributo — {{ $product->name }} - Ciclo Finca 4 Admin</title>
    @vite([
        'resources/css/admin/suppliers/suppliers.css',
        'resources/js/admin/product-classifications/edit.js',
    ])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main">
        <div class="form-container">
            <div class="form-header">
                <h1>{{ $product->name }}</h1>
                <p>
                    Elegí un <strong>valor</strong> por cada <strong>atributo</strong> (Color, Talla…). Categoría › subcategoría:
                    @if ($product->category)
                        {{ $product->category->parent->name ?? '' }} › <strong>{{ $product->category->name }}</strong>
                    @endif
                </p>
            </div>

            <div class="form-card">
                @if ($attributes->isEmpty())
                    <p>Todavía no hay atributos definidos para esta subcategoría. Configuralos en <strong>Opciones por tipo</strong> (menú lateral) y volvé acá.</p>
                @else
                    <form id="product-classifications-form"
                        action="{{ route('admin.products.classifications.update', $product) }}"
                        method="POST"
                        class="form-body"
                        data-initial-snapshot='@json($attributes->map(fn ($a) => (string) ($selectedByAttribute[$a->id] ?? ''))->values()->all())'>
                        @csrf
                        @method('PUT')

                        @foreach ($attributes as $attribute)
                            <div class="form-group">
                                <label for="attr_{{ $attribute->id }}">{{ $attribute->label }}</label>
                                <select id="attr_{{ $attribute->id }}" name="classification_value_ids[]" class="form-control">
                                    <option value="">— Ninguno —</option>
                                    @foreach ($attribute->values as $val)
                                        <option value="{{ $val->id }}"
                                            @selected(($selectedByAttribute[$attribute->id] ?? null) === (int) $val->id)>
                                            {{ $val->value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach

                        @if ($errors->any())
                            <x-admin-alert type="error" title="Revisa los campos marcados antes de continuar.">
                                <ul style="margin: 0; padding-left: 1.25rem;">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </x-admin-alert>
                        @endif

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                            <a href="{{ route('admin.product-classifications.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver al listado
                            </a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </main>
</body>

</html>
