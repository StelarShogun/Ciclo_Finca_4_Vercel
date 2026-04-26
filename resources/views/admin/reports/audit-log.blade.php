@php
    use App\Http\Controllers\Admin\AuditLogController;
@endphp

@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Bitácora de auditoría - Reportes')

@push('styles')
    @vite(['resources/css/admin/reports/reports-hub.css', 'resources/css/admin/reports/audit-log.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    <div class="audit-log-page">
        <nav class="reports-breadcrumb">
            <a href="{{ route('admin.reports.index') }}">Reportes</a>
            <span class="sep">/</span>
            <span>Bitácora de auditoría</span>
        </nav>

        <header class="audit-log-header">
            <h1>Bitácora de auditoría</h1>
            <p>Acciones administrativas registradas para seguimiento y control.</p>
        </header>

        <section class="audit-log-filters">
            <form method="GET" action="{{ route('admin.reports.audit-log') }}" class="audit-log-filters-grid">
                <label class="filter-field">
                    <span>Usuario</span>
                    <input type="text" name="user" value="{{ $filters['user'] }}" placeholder="Correo o nombre del admin">
                </label>

                <label class="filter-field">
                    <span>Tipo de acción</span>
                    <select name="action_type">
                        <option value="">Todas</option>
                        @foreach ($actionTypes as $type)
                            <option value="{{ $type }}" {{ $filters['action_type'] === $type ? 'selected' : '' }}>
                                {{ $actionTypeLabels[$type] ?? AuditLogController::actionTypeLabel($type) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="filter-field">
                    <span>Módulo</span>
                    <select name="module">
                        <option value="">Todos</option>
                        @foreach ($modules as $item)
                            <option value="{{ $item }}" {{ $filters['module'] === $item ? 'selected' : '' }}>
                                {{ $moduleLabels[$item] ?? AuditLogController::moduleLabel($item) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="filter-field">
                    <span>Desde</span>
                    <input type="date" name="from" value="{{ $filters['from'] }}">
                </label>

                <label class="filter-field">
                    <span>Hasta</span>
                    <input type="date" name="to" value="{{ $filters['to'] }}">
                </label>

                <label class="filter-field">
                    <span>Orden por fecha</span>
                    <select name="dir">
                        <option value="desc" {{ $filters['dir'] === 'desc' ? 'selected' : '' }}>Más reciente primero</option>
                        <option value="asc" {{ $filters['dir'] === 'asc' ? 'selected' : '' }}>Más antiguo primero</option>
                    </select>
                </label>

                <div class="filter-actions">
                    <button type="submit" class="btn-filter-apply">Aplicar filtros</button>
                    <a href="{{ route('admin.reports.audit-log') }}" class="btn-filter-clear">Limpiar</a>
                </div>
            </form>
        </section>

        <section class="audit-log-results">
            @if ($logs->isEmpty())
                <div class="audit-empty-state">
                    <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                    <h2>Sin registros para los filtros aplicados</h2>
                    <p>Probá cambiar usuario, módulo, tipo de acción o rango de fechas.</p>
                </div>
            @else
                <div class="table-wrap">
                    <table class="audit-log-table">
                        <thead>
                            <tr>
                                <th>Fecha y hora</th>
                                <th>Usuario</th>
                                <th>Tipo de acción</th>
                                <th>Módulo</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td data-label="Fecha y hora">{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
                                    <td data-label="Usuario">
                                        @if ($log->adminUser)
                                            {{ $log->adminUser->name }} {{ $log->adminUser->first_surname }}
                                            <div class="muted">{{ $log->adminUser->gmail }}</div>
                                        @else
                                            {{ $log->admin_email_snapshot ?? 'Sistema' }}
                                        @endif
                                    </td>
                                    <td data-label="Tipo de acción"><code>{{ $actionTypeLabels[$log->action_type] ?? AuditLogController::actionTypeLabel($log->action_type) }}</code></td>
                                    <td data-label="Módulo"><span class="module-pill">{{ $moduleLabels[$log->module] ?? AuditLogController::moduleLabel($log->module) }}</span></td>
                                    <td data-label="Descripción">{{ $log->description }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-pagination :paginator="$logs" label="auditoría" />
            @endif
        </section>
    </div>
@endsection
