@extends('client.layouts.app')

@section('hideNav')
@endsection

@section('title', 'Recuperar contraseña')

@section('content')
<div class="login-page-center">
    <div class="login-form-box" style="max-width:420px;width:100%;">
        <a href="{{ route('login.show') }}" class="login-back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Volver al inicio de sesión</span>
        </a>

        <h2 class="text-center mb-2">Recuperar contraseña</h2>
        <p class="login-subtitle text-center mb-4">Indica tu correo Gmail y te enviaremos un enlace para elegir una nueva contraseña.</p>

        @if (session('recovery_sent'))
            <div class="alert alert-success mb-3" role="alert">
                Si ese correo está registrado, recibirás un mensaje con las instrucciones en los próximos minutos.
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-3" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('clients.recovery.send') }}">
            @csrf
            <div class="form-group mb-3">
                <label for="recovery-gmail" class="login-field-label">
                    <i class="fas fa-envelope login-field-icon" aria-hidden="true"></i>
                    Correo Gmail
                </label>
                <input type="email" id="recovery-gmail" name="gmail" class="form-control" required
                    value="{{ old('gmail') }}" placeholder="ejemplo@gmail.com" autocomplete="email">
            </div>
            @if(config('recaptcha.site_key'))
                <div class="form-group mb-3">
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.key') }}"></div>
                </div>
            @endif
            <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">
                <i class="fas fa-paper-plane"></i>
                <span>Enviar enlace</span>
            </button>
        </form>
    </div>
</div>
@endsection

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
@endpush

@push('scripts')
@if(config('recaptcha.site_key'))
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
@endpush
