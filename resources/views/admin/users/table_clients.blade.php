<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Usuarios - Ciclo Finca 4 Admin</title>

    {{-- Styles & Fonts --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/admin/users/clients.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-layout">

    {{-- Sidebar navigation --}}
    @include('admin.parts.aside')

    <main class="admin-main">

        {{-- Page header with total user count --}}
        <div class="page-header">
            <div>
                <h1>Gestión de Usuarios</h1>
                <p class="text-muted">Administra los clientes registrados en la plataforma</p>
            </div>
            <div class="page-header-actions">
                <span class="clients-count-badge">
                    <i class="fas fa-users"></i>
                    {{ $clients->count() }} usuario(s)
                </span>
            </div>
        </div>

        {{-- ==================== USERS TABLE ==================== --}}
        <div class="clients-container">
            <div class="clients-table-wrapper">
                <table class="clients-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Primer Apellido</th>
                            <th>Segundo Apellido</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            <tr id="client-row-{{ $client->user_id }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $client->name }}</td>
                                <td>{{ $client->first_surname }}</td>
                                <td>{{ $client->second_surname ?? '—' }}</td>
                                <td>{{ $client->gmail }}</td>

                                {{-- Active / banned status badge --}}
                                <td>
                                    <span class="status-badge {{ $client->active ? 'status-active' : 'status-banned' }}">
                                        {{ $client->active ? 'Activo' : 'Baneado' }}
                                    </span>
                                </td>

                                {{-- Toggle ban/unban; data attributes consumed by clients.js --}}
                                <td>
                                    @if ($client->active)
                                        <button class="btn btn-danger btn-sm"
                                            data-id="{{ $client->user_id }}"
                                            data-name="{{ $client->name }} {{ $client->first_surname }}"
                                            data-email="{{ $client->gmail }}"
                                            data-action="ban">
                                            <i class="fas fa-ban"></i> Banear
                                        </button>
                                    @else
                                        <button class="btn btn-success btn-sm"
                                            data-id="{{ $client->user_id }}"
                                            data-name="{{ $client->name }} {{ $client->first_surname }}"
                                            data-email="{{ $client->gmail }}"
                                            data-action="unban">
                                            <i class="fas fa-check-circle"></i> Activar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="clients-empty">No hay usuarios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    {{-- SweetAlert2 for ban/unban confirmation dialogs --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- User management scripts --}}
    @vite(['resources/js/admin/users/clients.js'])

</body>
</html>