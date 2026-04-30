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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function () {
            const PRIMARY = '#2e7d32';
            const DANGER = '#dc2626';

            const form = document.getElementById('create-category-form');
            if (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.confirmed === '1') {
                        return;
                    }

                    event.preventDefault();

                    Swal.fire({
                        title: '¿Guardar esta categoría?',
                        text: 'Se creará una nueva categoría padre del catálogo.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: PRIMARY,
                        cancelButtonColor: DANGER,
                        confirmButtonText: 'Sí, guardar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                        focusCancel: true,
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.dataset.confirmed = '1';
                            form.submit();
                        }
                    });
                });
            }

            const successMessage = @json(session('status'));
            if (!successMessage) {
                return;
            }

            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: function (toastEl) {
                    toastEl.addEventListener('mouseenter', Swal.stopTimer);
                    toastEl.addEventListener('mouseleave', Swal.resumeTimer);
                },
            });

            Toast.fire({
                icon: 'success',
                title: successMessage,
            });
        })();
    </script>
</body>

</html>
