@extends('clients.layouts.guest')

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

        @if(request()->get('session_expired'))
            <div class="alert alert-warning mb-3" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                La sesión expiró o el token no es válido. Intenta iniciar sesión de nuevo.
            </div>
        @endif
        <h2 class="text-center">Bienvenido de nuevo</h2>
        <p class="login-subtitle text-center">Ingresa a tu cuenta para continuar</p>

        <form id="public-login-form" method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group mb-3">
                <label for="login-email" class="login-field-label">
                    <i class="fas fa-envelope login-field-icon" aria-hidden="true"></i>
                    Correo Electrónico
                </label>
                <input type="email" id="login-email" name="gmail" class="form-control" required placeholder="ejemplo@correo.com">
            </div>
            <div class="form-group mb-3 position-relative">
                <label for="login-password" class="login-field-label">
                    <i class="fas fa-lock login-field-icon" aria-hidden="true"></i>
                    Contraseña
                </label>
                <div style="position:relative;">
                    <input type="password" id="login-password" name="password" class="form-control" required placeholder="Ingresa tu contraseña" style="padding-right:40px;">
                    <button type="button" id="toggle-password" style="position:absolute;top:50%;right:8px;transform:translateY(-50%);background:none;border:none;cursor:pointer;">
                        <i class="fas fa-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>
            @if(config('services.recaptcha.site_key') || config('services.recaptcha.key'))
                <div class="form-group mb-3">
                    <label class="login-field-label recaptcha-label">
                        <i class="fas fa-shield-alt login-field-icon" aria-hidden="true"></i>
                        Completa la verificación a continuación
                    </label>
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') ?: config('services.recaptcha.key') }}"></div>
                </div>
            @else
                <input type="hidden" name="g-recaptcha-response" value="">
            @endif
            <div class="form-group mb-3">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Recordarme</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg mt-2" id="login-submit-btn">
                <i class="fas fa-sign-in-alt"></i>
                <span>Iniciar Sesión</span>
                <span class="btn-loading hidden" id="login-loading">Iniciando...</span>
            </button>
        </form>
        <div class="login-divider my-4 text-center">
            <span>o</span>
        </div>
        <div class="oauth-buttons text-center mb-3">
            <a href="{{ route('auth.google') }}" class="oauth-btn google-btn">
                <span class="google-g-icon" aria-hidden="true">G</span>
                <span class="google-text">
                    Continuar con
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
        <div class="login-footer text-center">
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
@if(config('services.recaptcha.site_key') || config('services.recaptcha.key'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
<script>
 document.getElementById('toggle-password').addEventListener('click', function() {
    const input = document.getElementById('login-password');
    const icon = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>
@endpush
@extends('clients.layouts.app')

@section('hideNav')
@endsection

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

        @if(request()->get('session_expired'))
            <div class="alert alert-warning mb-3" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                La sesión expiró o el token no es válido. Intenta iniciar sesión de nuevo.
            </div>
        @endif
        <h2 class="text-center mb-2">Bienvenido de nuevo</h2>
        <p class="login-subtitle text-center mb-4">Ingresa a tu cuenta para continuar</p>

        <form id="public-login-form" method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group mb-3">
                <label for="login-email" class="login-field-label">
                    <i class="fas fa-envelope login-field-icon" aria-hidden="true"></i>
                    Correo Electrónico
                </label>
                <input type="email" id="login-email" name="gmail" class="form-control" required placeholder="ejemplo@correo.com">
            </div>
            <div class="form-group mb-3 position-relative">
                <label for="login-password" class="login-field-label">
                    <i class="fas fa-lock login-field-icon" aria-hidden="true"></i>
                    Contraseña
                </label>
                <div style="position:relative;">
                    <input type="password" id="login-password" name="password" class="form-control" required placeholder="Ingresa tu contraseña" style="padding-right:40px;">
                    <button type="button" id="toggle-password" style="position:absolute;top:50%;right:8px;transform:translateY(-50%);background:none;border:none;cursor:pointer;">
                        <i class="fas fa-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>
            <div class="form-group mb-3">
                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.key') }}"></div>
            </div>
            <div class="form-group mb-3">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Recordarme</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg mt-2" id="login-submit-btn">
                <i class="fas fa-sign-in-alt"></i>
                <span>Iniciar Sesión</span>
                <span class="btn-loading hidden" id="login-loading">Iniciando...</span>
            </button>
        </form>
        <div class="login-divider my-4 text-center">
            <span>o</span>
        </div>
        <div class="oauth-buttons text-center mb-3">
            <a href="{{ route('auth.google') }}" class="oauth-btn google-btn">
                <span class="google-g-icon" aria-hidden="true">G</span>
                <span class="google-text">
                    Continuar con
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
        <div class="login-footer text-center">
            <p class="login-footer-text">¿No tienes una cuenta?</p>
            <a href="{{ route('clients.register.form') }}" class="login-register-btn">
                <i class="fas fa-user-plus" aria-hidden="true"></i>
                <span>Crear cuenta</span>
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
    @vite(['resources/css/clients-users.css'])
@endpush

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
 document.getElementById('toggle-password').addEventListener('click', function() {
    const input = document.getElementById('login-password');
    const icon = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>
@endpush
