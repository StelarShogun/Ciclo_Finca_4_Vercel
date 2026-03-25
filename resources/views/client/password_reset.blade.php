@extends('client.layouts.app')

@section('hideNav')
@endsection

@section('title', 'Nueva contraseña')

@section('content')
<div class="login-page-center">
    <div class="login-form-box" style="max-width:420px;width:100%;">
        <a href="{{ route('login.show') }}" class="login-back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Volver al inicio de sesión</span>
        </a>

        <h2 class="text-center mb-2">Nueva contraseña</h2>
        <p class="login-subtitle text-center mb-4">Elige una contraseña segura para tu cuenta.</p>

        @if ($errors->any())
            <div class="alert alert-danger mb-3" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('clients.recovery.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="gmail" value="{{ $gmail }}">

            <div class="form-group mb-3 position-relative">
                <label for="reset-password" class="login-field-label">
                    <i class="fas fa-lock login-field-icon" aria-hidden="true"></i>
                    Nueva contraseña
                </label>
                <input type="password" id="reset-password" name="password" class="form-control" required
                    minlength="8" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
            </div>
            <div class="form-group mb-3">
                <label for="reset-password-confirm" class="login-field-label">
                    <i class="fas fa-lock login-field-icon" aria-hidden="true"></i>
                    Confirmar contraseña
                </label>
                <input type="password" id="reset-password-confirm" name="password_confirmation" class="form-control" required
                    minlength="8" placeholder="Repite la contraseña" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">
                <i class="fas fa-check"></i>
                <span>Guardar contraseña</span>
            </button>
        </form>
    </div>
</div>
@endsection

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
@endpush
