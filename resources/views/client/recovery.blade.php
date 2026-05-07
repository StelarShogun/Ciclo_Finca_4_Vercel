@extends('client.layouts.app')

@section('hideNav')
@endsection

@section('title', 'Recuperar Contraseña')

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

        <h2 class="text-center mb-2">Recuperar Contraseña</h2>
        <p class="login-subtitle text-center mb-4">Ingresa tu correo y define una nueva contraseña</p>

        @if (session('unregistered_recovery_email'))
            <div class="alert alert-danger mb-3" role="alert">
                <div class="mb-1">Correo no está registrado.</div>
                <a href="{{ route('clients.register.form') }}" class="alert-link">Ir a registrarse</a>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-3" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form id="formRecovery" method="POST" action="{{ route('clients.recovery') }}" novalidate>
            @csrf

            {{-- Correo --}}
            <div class="form-group mb-3">
                <label for="recovery-email" class="login-field-label">
                    <i class="fas fa-envelope login-field-icon" aria-hidden="true"></i>
                    Correo Electrónico
                </label>
                <input type="email" id="recovery-email" name="gmail"
                       class="form-control"
                       required
                       value="{{ old('gmail') }}"
                       placeholder="ejemplo@gmail.com"
                       autocomplete="email">
                <div id="msg-recovery-email" class="field-msg"></div>
            </div>

            {{-- Nueva contraseña --}}
            <div class="form-group mb-3">
                <label for="recovery-password" class="login-field-label">
                    <i class="fas fa-lock login-field-icon" aria-hidden="true"></i>
                    Nueva Contraseña
                </label>
                <div class="login-pass-wrap">
                    <input type="password" id="recovery-password" name="new_password"
                           class="form-control"
                           required
                           minlength="8"
                           placeholder="Mínimo 8 caracteres"
                           autocomplete="new-password">
                    <button type="button" id="toggle-recovery-password" class="login-pass-toggle">
                        <i class="fas fa-eye" id="eye-recovery-password"></i>
                    </button>
                </div>
                <div id="msg-recovery-password" class="field-msg"></div>
            </div>

            {{-- Confirmar contraseña --}}
            <div class="form-group mb-4">
                <label for="recovery-password-confirm" class="login-field-label">
                    <i class="fas fa-lock login-field-icon" aria-hidden="true"></i>
                    Confirmar Nueva Contraseña
                </label>
                <div class="login-pass-wrap">
                    <input type="password" id="recovery-password-confirm" name="new_password_confirmation"
                           class="form-control"
                           required
                           minlength="8"
                           placeholder="Repite la contraseña"
                           autocomplete="new-password">
                    <button type="button" id="toggle-recovery-confirm" class="login-pass-toggle">
                        <i class="fas fa-eye" id="eye-recovery-confirm"></i>
                    </button>
                </div>
                <div id="msg-recovery-confirm" class="field-msg"></div>
            </div>

            <button type="submit" id="btnRecovery" class="btn btn-primary btn-block btn-lg mt-2">
                <i class="fas fa-key"></i>
                <span id="btnRecoveryTexto">Actualizar Contraseña</span>
                <span id="btnRecoveryCargando" style="display:none;">Actualizando...</span>
            </button>
        </form>

        <div class="login-footer text-center mt-3">
            <p>¿Ya tienes cuenta? <a href="{{ route('login.show') }}">Iniciar sesión</a></p>
        </div>

    </div>
</div>
@endsection

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
@endpush

@push('scripts')
    @vite(['resources/js/client/clients-users.js'])
@endpush