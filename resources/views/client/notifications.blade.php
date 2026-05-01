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
    </div>
</div>

<div class="cf4-invoices-wrapper">
    <div class="cf4-invoices-card">
        <div class="sales-table-container">
            <table class="sales-table cf4-purchases-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Mensaje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                        <tr>
                            <td>{{ optional($notification->created_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ data_get($notification->data, 'message', 'Notificación del sistema') }}</td>
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

        <div style="margin-top:16px;">
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection
