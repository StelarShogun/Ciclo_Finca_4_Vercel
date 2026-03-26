<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Usuarios - Ciclo Finca 4 Admin</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/admin.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-layout">

    @include('partes.aside')

    <main class="admin-main">
        <header class="page-header">
            <div>
                <h1>Gestión de Usuarios</h1>
                <p>Administra los clientes registrados en la plataformass</p>
            </div>
            <div class="page-header-actions">
                <span class="clients-count-badge">
                    <i class="fas fa-users"></i>
                    {{ $clients->count() }} usuario(s)
                </span>
            </div>
        </header>

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
                                <td>
                                    <span class="status-badge {{ $client->active ? 'status-active' : 'status-banned' }}">
                                        {{ $client->active ? 'Activo' : 'Baneado' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($client->active)
                                        <button
                                            class="btn-ban"
                                            data-id="{{ $client->user_id }}"
                                            data-name="{{ $client->name }} {{ $client->first_surname }}"
                                            data-email="{{ $client->gmail }}"
                                            data-action="ban">
                                            <i class="fas fa-ban"></i> Banear
                                        </button>
                                    @else
                                        <button
                                            class="btn-unban"
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/js/admin/admin.js'])

</body>
</html>
