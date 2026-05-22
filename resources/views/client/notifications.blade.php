@extends('client.layouts.app')

@section('title', 'Mis Notificaciones')

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
@endpush

@section('content')
<div class="cf4-invoices-header">
    <div class="cf4-invoices-header-inner">
        <h1><i class="fas fa-bell"></i> Mis Notificaciones</h1>
        <p>Historial de avisos enviados por el sistema.</p>
        <nav class="cf4-invoices-escape-nav" aria-label="Navegación">
            <a href="{{ route('clients.home') }}" class="cf4-invoices-escape-link">
                <i class="fas fa-home" aria-hidden="true"></i> Ir al inicio
            </a>
        </nav>
    </div>
</div>

<div class="cf4-invoices-wrapper">
    <nav class="breadcrumb" aria-label="Migas de pan">
        <a href="{{ route('clients.home') }}">Inicio</a>
        <span>/</span>
        <span>Notificaciones</span>
    </nav>

    <div class="cf4-invoices-card">
        <div class="sales-table-container">
            <table class="sales-table cf4-purchases-table admin-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Mensaje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                        <tr>
                            <td data-label="Fecha">{{ optional($notification->created_at)->format('d/m/Y H:i') }}</td>
                            <td data-label="Mensaje">
                                <div class="cf4-notification-message">
                                    {{ data_get($notification->data, 'message', 'Notificación del sistema') }}
                                </div>
                                @php
                                    $actionUrl = data_get($notification->data, 'action_url');
                                    $actionLabel = data_get($notification->data, 'action_label', 'Abrir enlace');
                                @endphp
                                @if(! empty($actionUrl))
                                    <div class="cf4-notification-action">
                                        <a href="{{ $actionUrl }}">{{ $actionLabel }}</a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                <div class="cf4-invoices-empty">
                                    <div class="cf4-invoices-empty-icon"><i class="fas fa-bell-slash"></i></div>
                                    <p>No tienes notificaciones.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($notifications->hasPages())
            <div class="cf4-invoices-pagination-wrap">
                <x-pagination :paginator="$notifications" label="notificaciones" />
            </div>
        @endif
    </div>
</div>
@endsection
