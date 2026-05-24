<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Usuarios - Ciclo Finca 4 Admin</title>

    {{-- Styles & Fonts --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/components/page-header.css', 'resources/css/admin/users/clients.css'])
</head>

<body class="admin-layout">

    {{-- Sidebar navigation --}}
    @include('admin.parts.aside')

    <main class="admin-main admin-main--content">
        <div class="admin-content-wrapper">
        {{-- Page header with total user count --}}
@component('admin.partials.page-header', [
    'title' => 'Gestión de usuarios',
    'description' => 'Consulta y administra los clientes registrados en la plataforma, incluyendo su estado de acceso.',
])
            @slot('actions')
                <div class="page-header-actions">
                    <span class="clients-count-badge">
                        <i class="fas fa-users"></i>
                        {{ $clients->total() }} usuario(s)
                    </span>
                </div>
            @endslot
        @endcomponent

        @component('admin.partials.filters', [
            'action' => route('admin.clients.index'),
            'clearUrl' => route('admin.clients.index'),
            'formId' => 'clients-filters-form',
        ])
            @slot('fields')
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">

                <div class="filter-group">
                    <label for="client-search">Buscar</label>
                    <input type="text" id="client-search" name="search" placeholder="Nombre, apellido o correo…"
                        value="{{ request('search') }}">
                </div>

                <div class="filter-group">
                    <label for="client-status">Estado</label>
                    <select id="client-status" name="status">
                        <option value="">Todos los estados</option>
                        <option value="active" @selected(request('status') === 'active')>Activo</option>
                        <option value="banned" @selected(request('status') === 'banned')>Baneado</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="client-created-date">Creado</label>
                    <input type="date" id="client-created-date" name="created_date" value="{{ request('created_date') }}">
                </div>

                <div class="filter-group">
                    <label for="client-updated-date">Última actualización</label>
                    <input type="date" id="client-updated-date" name="updated_date" value="{{ request('updated_date') }}">
                </div>
            @endslot
        @endcomponent

        {{-- ==================== USERS TABLE ==================== --}}
        @php
            $clientSortUrl = static function (string $column) use ($sort, $dir): string {
                $nextDir = $sort === $column && $dir === 'asc' ? 'desc' : 'asc';

                return route('admin.clients.index', array_merge(
                    request()->except(['page', 'sort', 'dir']),
                    ['sort' => $column, 'dir' => $nextDir, 'page' => 1],
                ));
            };
        @endphp

        <div class="clients-container table-section" data-cf4-ajax-pagination data-cf4-ajax-scroll>
            <div id="cf4-list-fragment">
            <div class="clients-table-wrapper">
                <table class="clients-table admin-table">
                    <thead>
                        <tr>
                            <th scope="col">
                                <a href="{{ $clientSortUrl('name') }}"
                                    class="th-sort {{ $sort === 'name' ? 'is-active' : '' }}">
                                    Nombre
                                    @if ($sort === 'name')
                                        <i class="fas fa-sort-{{ $dir === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">
                                <a href="{{ $clientSortUrl('first_surname') }}"
                                    class="th-sort {{ $sort === 'first_surname' ? 'is-active' : '' }}">
                                    Primer Apellido
                                    @if ($sort === 'first_surname')
                                        <i class="fas fa-sort-{{ $dir === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">
                                <a href="{{ $clientSortUrl('second_surname') }}"
                                    class="th-sort {{ $sort === 'second_surname' ? 'is-active' : '' }}">
                                    Segundo Apellido
                                    @if ($sort === 'second_surname')
                                        <i class="fas fa-sort-{{ $dir === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">
                                <a href="{{ $clientSortUrl('gmail') }}"
                                    class="th-sort {{ $sort === 'gmail' ? 'is-active' : '' }}">
                                    Correo
                                    @if ($sort === 'gmail')
                                        <i class="fas fa-sort-{{ $dir === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">
                                <a href="{{ $clientSortUrl('created_at') }}"
                                    class="th-sort {{ $sort === 'created_at' ? 'is-active' : '' }}">
                                    Creado
                                    @if ($sort === 'created_at')
                                        <i class="fas fa-sort-{{ $dir === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">
                                <a href="{{ $clientSortUrl('updated_at') }}"
                                    class="th-sort {{ $sort === 'updated_at' ? 'is-active' : '' }}">
                                    Actualizado
                                    @if ($sort === 'updated_at')
                                        <i class="fas fa-sort-{{ $dir === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">
                                <a href="{{ $clientSortUrl('active') }}"
                                    class="th-sort {{ $sort === 'active' ? 'is-active' : '' }}">
                                    Estado
                                    @if ($sort === 'active')
                                        <i class="fas fa-sort-{{ $dir === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="admin-table__col--actions">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            <tr id="client-row-{{ $client->user_id }}">
                                <td>{{ $client->name }}</td>
                                <td>{{ $client->first_surname }}</td>
                                <td>{{ $client->second_surname ?? '—' }}</td>
                                <td>{{ $client->gmail }}</td>
                                <td>{{ $client->created_at?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $client->updated_at?->format('d/m/Y') ?? '—' }}</td>

                                {{-- Active / banned status badge --}}
                                <td>
                                    <span class="status-badge {{ $client->active ? 'status-active' : 'status-banned' }}">
                                        {{ $client->active ? 'Activo' : 'Baneado' }}
                                    </span>
                                </td>

                                {{-- Toggle ban/unban; data attributes consumed by clients.js --}}
                                <td class="admin-table__col--actions">
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
                                <td colspan="8" class="clients-empty">
                                    @if (request()->hasAny(['search', 'status', 'created_date', 'updated_date']))
                                        No hay usuarios para los filtros seleccionados.
                                    @else
                                        No hay usuarios registrados.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrapper">
                <x-admin.pagination :paginator="$clients" label="usuarios" />
            </div>
            </div>
        </div>
        </div>
    </main>

    {{-- SweetAlert2 for ban/unban confirmation dialogs --}}


    {{-- User management scripts --}}
    @vite(['resources/js/admin/shell.js', 'resources/js/admin/users/clients.js'])

</body>
</html>