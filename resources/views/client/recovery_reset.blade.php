@extends('client.layouts.app')

@section('hideNav')
@endsection

@section('title', 'Nueva Contraseña')

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
@endpush

@section('content')
<div class="login-page-center">
    <div class="login-form-box" style="max-width:480px;">

        <a href="{{ route('login.show') }}" class="login-back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Volver al inicio de sesión</span>
        </a>

        <div class="login-auth-logo">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Ciclo Finca 4">
        </div>

        <h2 class="text-center mb-2">Nueva Contraseña</h2>
        <p class="login-subtitle text-center mb-4">
            Define una nueva contraseña
            @if(!empty($gmail))
                para <strong>{{ $gmail }}</strong>
            @endif
        </p>

        @if ($errors->any())
            <div class="alert alert-danger mb-3" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form id="formRecoveryReset" method="POST" action="{{ route('clients.recovery.reset') }}" novalidate>
            @csrf

            {{-- Nueva contraseña --}}
            <div class="form-group mb-3">
                <label for="reset-password" class="login-field-label">
                    <i class="fas fa-lock login-field-icon" aria-hidden="true"></i>
                    Nueva Contraseña
                </label>
                <div class="login-pass-wrap">
                    <input type="password" id="reset-password" name="new_password"
                           class="form-control"
                           required
                           minlength="8"
                           placeholder="Mínimo 8 caracteres"
                           autocomplete="new-password">
                    <button type="button" id="toggle-reset-password" class="login-pass-toggle">
                        <i class="fas fa-eye" id="eye-reset-password"></i>
                    </button>
                </div>
                <div id="msg-reset-password" class="field-msg"></div>
            </div>

            {{-- Confirmar contraseña --}}
            <div class="form-group mb-4">
                <label for="reset-password-confirm" class="login-field-label">
                    <i class="fas fa-lock login-field-icon" aria-hidden="true"></i>
                    Confirmar Nueva Contraseña
                </label>
                <div class="login-pass-wrap">
                    <input type="password" id="reset-password-confirm" name="new_password_confirmation"
                           class="form-control"
                           required
                           minlength="8"
                           placeholder="Repite la contraseña"
                           autocomplete="new-password">
                    <button type="button" id="toggle-reset-confirm" class="login-pass-toggle">
                        <i class="fas fa-eye" id="eye-reset-confirm"></i>
                    </button>
                </div>
                <div id="msg-reset-confirm" class="field-msg"></div>
            </div>

            <button type="submit" id="btnRecoveryReset" class="btn btn-primary btn-block btn-lg mt-2">
                <i class="fas fa-key"></i>
                <span id="btnRecoveryResetTexto">Actualizar Contraseña</span>
                <span id="btnRecoveryResetCargando" style="display:none;">Actualizando...</span>
            </button>
        </form>

        <div class="login-footer text-center mt-3">
            <p>¿Ya tienes cuenta? <a href="{{ route('login.show') }}">Iniciar sesión</a></p>
        </div>

    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/client/clients-users.js'])
@endpush
