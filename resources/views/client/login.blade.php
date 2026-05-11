@extends('client.layouts.app')

@section('hideNav')
@endsection

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
@endpush

@section('title', 'Iniciar Sesión')

@section('content')
<div class="login-page-center">
    <div class="login-form-box">
        <a href="{{ route('clients.home') }}" class="login-back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Regresar</span>
        </a>

        <div class="login-auth-logo">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Ciclo Finca 4">
        </div>

        {{-- Show warning if session expired or token is invalid --}}
        @if(request()->get('session_expired'))
            <div class="alert alert-warning mb-3" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                La sesión expiró o el token no es válido. Intenta iniciar sesión de nuevo.
            </div>
        @endif

        {{-- Show success message (e.g. after password reset) --}}
        @if (session('status') && !session('recovery_success_modal'))
            <div class="alert alert-success mb-3" role="alert">
                <i class="fas fa-check-circle"></i>
                {{ session('status') }}
            </div>
        @endif

        <h2>Bienvenido de nuevo</h2>
        <p class="login-subtitle">Ingresa a tu cuenta para continuar</p>

        {{-- Register URL exposed via data attribute for JS access --}}
        <form
            id="public-login-form"
            method="POST"
            action="{{ route('login') }}"
            data-register-url="{{ route('clients.register.form') }}"
        >
            @csrf
            <div class="form-group">
                <label for="login-email" class="login-field-label">
                    <i class="fas fa-envelope login-field-icon" aria-hidden="true"></i>
                    Correo Electrónico
                </label>
                <input type="email" id="login-email" name="gmail" class="form-control" required placeholder="ejemplo@correo.com">
            </div>
            <div class="form-group">
                <label for="login-password" class="login-field-label">
                    <i class="fas fa-lock login-field-icon" aria-hidden="true"></i>
                    Contraseña
                </label>
                {{-- Toggle button controls password visibility via JS --}}
                <div class="login-pass-wrap">
                    <input type="password" id="login-password" name="password" class="form-control" required placeholder="Ingresa tu contraseña">
                    <button type="button" id="toggle-password" class="login-pass-toggle">
                        <i class="fas fa-eye" id="eye-icon"></i>
                    </button>
                </div>
                <div class="text-right mt-1" style="text-align:right;">
                    <a href="{{ route('clients.recovery.form') }}" class="login-field-label" style="font-size:0.85rem;color:#2d7a2d;">
                        ¿Olvidó su contraseña?
                    </a>
                </div>
            </div>

            {{-- reCAPTCHA v2 widget --}}
            <div class="form-group recaptcha-wrap">
                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.key') }}"></div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Recordarme</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-login-submit" id="login-submit-btn">
                <i class="fas fa-sign-in-alt"></i>
                <span>Iniciar Sesión</span>
            </button>
        </form>

        <div class="login-divider">
            <span>o</span>
        </div>

        {{-- OAuth: Google login --}}
        <div class="oauth-buttons">
            <a href="{{ route('auth.google') }}" class="oauth-btn google-btn">
                <span class="google-g-icon" aria-hidden="true">G</span>
                <span class="google-text">
                    Continuar con
                    {{-- Multicolor Google brand letters --}}
                    <span class="google-brand" aria-hidden="true">
                        <span class="brand-letter brand-g">G</span>
                        <span class="brand-letter brand-o">o</span>
                        <span class="brand-letter brand-o2">o</span>
                        <span class="brand-letter brand-g2">g</span>
                        <span class="brand-letter brand-l">l</span>
                        <span class="brand-letter brand-e">e</span>
                    </span>
                </span>
            </a>
        </div>

        <div class="login-footer">
            <p class="login-footer-text">¿No tienes una cuenta?</p>
            <a href="{{ route('clients.register.form') }}" class="login-register-btn">
                <i class="fas fa-user-plus" aria-hidden="true"></i>
                <span>Crear cuenta</span>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- reCAPTCHA script loaded async to avoid blocking render --}}
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @vite(['resources/js/client/clients-users.js'])
    @if (session('recovery_success_modal'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Contraseña actualizada',
                    text: @json(session('recovery_success_modal')),
                    confirmButtonText: 'Aceptar',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(function () {
                    window.location.href = @json(route('login.show'));
                });
            });
        </script>
    @endif
@endpush