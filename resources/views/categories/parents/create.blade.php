<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crear categoría - Ciclo Finca 4 Admin</title>

    @vite(['resources/css/admin/suppliers/suppliers.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main">
        <div class="form-container">
            <div class="form-header">
                <h1>Crear categoría</h1>
                <p>Definí una categoría principal del catálogo; luego podés agregar subcategorías y asignar productos.</p>
            </div>

            <div class="form-card">
                <form id="create-category-form" action="{{ route('categories.parents.store') }}" method="POST" class="form-body">
                    @csrf

                    <div class="form-group">
                        <label for="name">Nombre de la categoría *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            placeholder="Ej. Iluminación, Llantas"
                            autocomplete="off">
                        <div class="error-message">{{ $errors->first('name') }}</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción</label>
                        <textarea id="description" name="description" rows="3"
                            placeholder="Opcional.">{{ old('description') }}</textarea>
                        <div class="error-message">{{ $errors->first('description') }}</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Guardar categoría
                        </button>

                        <a href="{{ route('inventory') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al inventario
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script>
        (function() {
            const form = document.getElementById('create-category-form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    const ok = window.confirm('¿Deseás guardar esta categoría?');
                    if (!ok) {
                        event.preventDefault();
                    }
                });
            }

            const successMessage = @json(session('status'));
            if (!successMessage) {
                return;
            }

            const toast = document.createElement('div');
            toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + successMessage;
            toast.style.position = 'fixed';
            toast.style.right = '20px';
            toast.style.top = '20px';
            toast.style.zIndex = '9999';
            toast.style.background = '#16a34a';
            toast.style.color = '#fff';
            toast.style.padding = '12px 16px';
            toast.style.borderRadius = '10px';
            toast.style.boxShadow = '0 8px 24px rgba(0, 0, 0, 0.18)';
            toast.style.fontWeight = '600';
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.gap = '8px';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            toast.style.transition = 'all 220ms ease';

            document.body.appendChild(toast);

            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });

            window.setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                window.setTimeout(() => toast.remove(), 240);
            }, 2800);
        })();
    </script>
</body>

</html>
